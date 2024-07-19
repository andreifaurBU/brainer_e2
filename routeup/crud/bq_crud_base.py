#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""BigQuery Data Manipulation Handler.

This class is used to load, update and delete data in BigQuery.
"""
import json
import time
from typing import Generic, Type, TypeVar

from google.api_core.exceptions import NotFound
from google.cloud import bigquery
from pandera.decorators import check_output
from pandera.typing import DataFrame

from routeup.core import logger
from routeup.core.exceptions import NotExistingJSONSchema, TableCreationTimeout
from routeup.db.bigquery_handler import BigQueryDataHandler
from routeup.schemas_df.base import BaseDFSchema

DFSchemaType = TypeVar("DFSchemaType", bound=BaseDFSchema)


class BigQueryCRUDBase(BigQueryDataHandler, Generic[DFSchemaType]):
    """Handler for data manipulation."""

    def __init__(
        self,
        *,
        project_id: str,
        dataset_id: str,
        table_id: str,
        schema_path: str | None = None,
        schema_df: Type[DFSchemaType],
        primary_key: str,
        timeout: int = 60,
    ):
        """Initialize the CRUD handler.

        Args:
            project_id: BigQuery project id.
            dataset_id: BigQuery dataset id.
            table_id: BigQuery table id.
            schema_path: Path to the schema file, if any.
            schema_df: Panderas schema of the table.
            primary_key: Primary key of the table.
            timeout: Timeout for table creation.
        """
        super().__init__(project_id=project_id, dataset_id=dataset_id)
        self.table_id = table_id
        self.schema_path = schema_path
        self.schema_df = schema_df
        self.primary_key = primary_key
        self.read = check_output(schema_df.to_schema(), inplace=True)(self.read)  # type: ignore[method-assign]
        self.write = check_output(schema_df.to_schema(), inplace=True)(self.write)  # type: ignore[method-assign]
        self.upsert = check_output(schema_df.to_schema(), inplace=True)(self.upsert)  # type: ignore[method-assign]
        self.timeout = timeout

    def read(self, condition: str | None = None) -> DataFrame[DFSchemaType]:
        """Read data from table.

        Args:
            condition: if provided, filter the data to be read.

        Returns:
            Dataframe with the data read from the table.
        """
        # Construct the query to fetch data from the specified table
        query = f"SELECT * FROM `{self.project_id}.{self.dataset_id}.{self.table_id}`"
        if condition:
            query += f" WHERE {condition}"
        # Run the query
        df = self.client.query(query).to_dataframe()
        return df  # type: ignore[return-value]

    def get_number_of_rows(self, *, table_name: str) -> int:
        """Get number of rows in table.

        Args:
            table_name (str): Table name.
        """
        query = (
            f"SELECT COUNT(*) FROM `{self.project_id}.{self.dataset_id}.{table_name}`"
        )
        result = self.client.query(query).result()
        return [row[0] for row in result][0]

    def is_table_empty(self, *, table_name: str) -> bool:
        """Check if table is empty.

        Args:
            table_name (str): Table name.
        """
        nrows = self.get_number_of_rows(table_name=table_name)
        return nrows == 0

    def upsert(self, *, df: DataFrame[DFSchemaType]) -> DataFrame[DFSchemaType]:
        """Update or append data in table.

        Args:
            df: DataFrame to update or append.

        Raises:
            ValueError: If the DataFrame does not have a 'service_id' column.
        """
        table_id = f"{self.project_id}.{self.dataset_id}.{self.table_id}"
        # check if table exists
        if not self.is_table_in_db(table_name=self.table_id):
            logger.info(f"Table {table_id} not found, creating it.")
            self.write(df=df, asynchronous=False)
            return df

        # create view for merging
        temp_view = f"temp_view_for_update_{self.table_id}"
        temp_view_id = f"{self.project_id}.{self.dataset_id}.{temp_view}"
        with open(self.schema_path) as schema_file:
            schema = json.load(schema_file)
        job_config = bigquery.LoadJobConfig(
            schema=schema,
            write_disposition="WRITE_APPEND",
        )
        # this next operation is asynchronous
        self.client.load_table_from_dataframe(df, temp_view_id, job_config=job_config)

        # Use the MERGE statement to update or append records
        merge_query = f"""
            MERGE `{table_id}` AS target
            USING `{temp_view_id}` AS source
            ON target.{self.primary_key} = source.{self.primary_key}
            WHEN MATCHED THEN
                UPDATE SET {", ".join([f'target.{col} = source.{col}' for col in df.columns if col != self.primary_key])}
            WHEN NOT MATCHED THEN
                INSERT ROW
        """
        try:
            # Wait unitl the table has been created if the table has been created
            i = 0
            while not self.is_table_in_db(table_name=temp_view) and i < self.timeout:
                time.sleep(1)
                i += 1
            if i == self.timeout:
                logger.error(f"Table {temp_view_id} not created, timeout reached.")
                return df
            else:
                self.client.query(merge_query).result()
        except NotFound:
            logger.error(f"Can not find table {table_id}.")
            self.write(df=df)
            logger.debug(f"Table {table_id} created.")
        finally:
            self.client.delete_table(temp_view_id, not_found_ok=True)

        logger.debug("Update process completed.")
        return df

    def write(
        self,
        *,
        df: DataFrame[DFSchemaType],
        disposition: str = "APPEND",
        asynchronous: bool = True,
    ) -> DataFrame[DFSchemaType]:
        """Write data to table. It assumes pk constraint is not violated.

        Args:
            df: DataFrame to write.
            disposition: Write disposition. It an be APPEND, TRUNCATE or EMPTY.
            asynchronous: If True, the method will wait until the table is created.
        """
        # Load the schema of the table
        table_id = f"{self.project_id}.{self.dataset_id}.{self.table_id}"
        if self.schema_path:
            schema = self.client.schema_from_json(self.schema_path)
            job_config = bigquery.LoadJobConfig(
                schema=schema,
                write_disposition=f"WRITE_{disposition}",
            )
            self.client.load_table_from_dataframe(df, table_id, job_config=job_config)
            # wait until the table has been created
            i = 0
            while (
                not self.is_table_in_db(table_name=self.table_id)
                and i < self.timeout
                and not asynchronous
            ):
                time.sleep(1)
                i += 1
            if i < self.timeout:
                logger.debug(f"Loaded {len(df)} rows into {table_id}.")
            else:
                logger.error(f"Cannot create elements in table {table_id}.")
                raise TableCreationTimeout("Reached table creation timeout.")
        else:
            logger.error(f"Cannot create elements in table {table_id}.")
            raise NotExistingJSONSchema("Schema path not provided.")
        return df

    def delete(self, condition: str | None = None):
        """Delete data from table.

        Args:
            condition: Condition to filter the data to be deleted.

        """
        query = f"DELETE FROM `{self.project_id}.{self.dataset_id}.{self.table_id}`"
        if condition:
            query += f" WHERE {condition}"
        self.client.query(query)
        logger.debug(f"Deleted data from {self.table_id}.")
