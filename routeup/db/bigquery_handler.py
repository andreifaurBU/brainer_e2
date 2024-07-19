#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""BigQuery handler."""
import warnings
from typing import List

from google.cloud import bigquery
from google.oauth2 import service_account

from routeup.core import config

# ignore _CLOUD_SDK_CREDENTIALS_WARNING
warnings.filterwarnings(action="ignore", category=UserWarning)
#
# bigquery_client = bigquery.Client(
#     project=config.GCLOUD_PROJECT_ID,
#     _http=AuthorizedSession(config.BIGQUERY_HTTP) if config.BIGQUERY_HTTP else None,
# )

credentials = service_account.Credentials.from_service_account_file(
    filename=config.GCLOUD_CREDENTIALS_PATH
)
bigquery_client = bigquery.Client(
    credentials=credentials, project=credentials.project_id
)


class BigQueryDataHandler:
    """BigQuery Data Handler."""

    def __init__(self, *, project_id: str, dataset_id: str):
        """Initialize the handler.

        Args:
            project_id: BigQuery project id.
            dataset_id: BigQuery dataset id.
        """
        self.project_id = project_id
        self.dataset_id = dataset_id
        self.client = bigquery_client
        assert (
            self.project_id == self.client.project
        ), f"Project ID mismatch: {self.project_id}, {self.client.project}"

    def list_tables(self) -> List:
        """List tables in dataset."""
        # List tables in the specified dataset
        dataset_ref = bigquery.DatasetReference(self.project_id, self.dataset_id)
        tables = list(self.client.list_tables(dataset_ref))

        # Extract table names as strings
        table_names = [table.table_id for table in tables]

        return table_names

    def is_table_in_db(self, *, table_name: str) -> bool:
        """Check if table exists.

        Args:
            table_name (str): Table name.
        """
        return table_name in self.list_tables()
