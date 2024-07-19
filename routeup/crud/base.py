#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**CRUD Base classes**.

Here we define the basic CRUD operations to perform against the
``routeup`` database.

If an operation has to be performed similarly on every table,
it may be included in the :class:`~.CRUDBase` class.

"""

from typing import Any, Generic, Sequence, Type, TypeVar

import pandas as pd
from pandera.decorators import check_output
from pandera.typing import DataFrame
from sqlalchemy import insert, select, update
from sqlalchemy.orm import DeclarativeBase, Session

from routeup.core import logger
from routeup.schemas.base import BaseSchema
from routeup.schemas_df.base import BaseDFSchema

BaseModelType = TypeVar("BaseModelType", bound=DeclarativeBase)
CreateSchemaType = TypeVar("CreateSchemaType", bound=BaseSchema)
UpdateSchemaType = TypeVar("UpdateSchemaType", bound=BaseSchema)
DFSchemaType = TypeVar("DFSchemaType", bound=BaseDFSchema)


class CRUDBase(
    Generic[BaseModelType, CreateSchemaType, UpdateSchemaType, DFSchemaType]
):
    """Base CRUD class.

    Here we encode the most basic CRUD operations, that
    every CRUD subclass might have.

    The database session object must be the first argument
    of every CRUD method.

    """

    def __init__(self, model: Type[BaseModelType], schema_df: Type[DFSchemaType]):
        """**Instance parameters**.

        The CRUD object internal state depends on the ORM mapping
        the database table the object is intended to interact with,
        and the Pandera DataFrame Schema representing such table.

        The ORM is used to interact with the database table using
        SQLAlchemy, while the Pandera Schema is used to validate data
        coming from the database table, when accessed with Pandas.

        Args:
            model: A SQLAlchemy ORM.
            schema_df: A Pandera DataFrame model.
        """
        self.model = model
        self.schema_df = schema_df
        self.get_df = check_output(schema_df.to_schema(), inplace=True)(self.get_df)  # type: ignore[method-assign]

    def get_multi(
        self,
        db: Session,
        *,
        skip: int = None,
        limit: int = None,
    ) -> Sequence[BaseModelType]:
        """Get multiple elements.

        Args:
            db: Database object.
            skip: Number of elements to skip.
            limit: Maximum number of elements to return.

        Returns: List of database elements.

        """
        r = db.execute(select(self.model).offset(skip).limit(limit)).scalars().all()
        logger.info(f"Got {len(r)} records from '{self.model.__tablename__}'")
        return r

    def get_df(self, db: Session) -> DataFrame[DFSchemaType]:
        """Get the database table as a Pandas DataFrame.

        Args:
            db: Database session.

        Returns: The database table as a Pandas DataFrame.

        """
        return DataFrame(pd.read_sql(select(self.model), db.connection()))

    def create(self, db: Session, *, obj_in: CreateSchemaType) -> BaseModelType:
        """Create an element in the database.

        Args:
            db: Database object.
            obj_in: Schema with the new element information.

        Returns: The created element as a SQLAlchemy ORM.

        """
        obj_in_data = obj_in.model_dump()
        db_obj = self.model(**obj_in_data)  # type: ignore
        db.add(db_obj)
        db.commit()
        db.refresh(db_obj)
        return db_obj

    def create_multi(self, db: Session, *, data: list[CreateSchemaType]):
        """Create multiple elements.

        This method follows a bulk creation strategy, instead of
        creating the elements one by one.

        Args:
            db: Database object.
            data: Data entries to be created.

        """
        mappings = [x.model_dump() for x in data]
        logger.info(
            f"Creating {len(mappings)} new records in table "
            f"'{self.model.__tablename__}'"
        )
        db.execute(insert(self.model), mappings)
        db.commit()

    def update(
        self,
        db: Session,
        *,
        db_obj: BaseModelType,
        obj_in: UpdateSchemaType | dict[str, Any],
    ) -> BaseModelType:
        """Update an element that already exists in the database.

        Args:
            db: Database object.
            db_obj: Database element to update.
            obj_in: Schema with the update information.

        Returns: The updated element as a SQLAlchemy ORM.

        """
        obj_data = {k: v for k, v in vars(db_obj).items() if not k.startswith("_")}
        if isinstance(obj_in, dict):
            update_data = obj_in
        else:
            update_data = obj_in.model_dump(exclude_unset=True)
        for field in obj_data:
            if field in update_data:
                setattr(db_obj, field, update_data[field])
        db.add(db_obj)
        db.commit()
        db.refresh(db_obj)
        return db_obj

    def update_multi(self, db: Session, *, data: list[UpdateSchemaType]):
        """Update multiple elements.

        This method follows a bulk update strategy, instead of
        updating elements one by one.

        Args:
            db: Database object.
            data: Data entries to be updated.

        """
        mappings = [x.model_dump() for x in data]
        logger.info(
            f"Updating {len(mappings)} records of table '{self.model.__tablename__}'"
        )
        db.execute(update(self.model), mappings)
        db.commit()


__all__ = ["CRUDBase"]
