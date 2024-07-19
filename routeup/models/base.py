#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**SQLAlchemy ORM base class**.

Here we define the declarative base for our ORMs.

Every ORM defined within the :mod:`~routeup.models`
module should inherit from this base class.

.. doctest::

    >>> from sqlalchemy.orm import Mapped, mapped_column
    >>> from routeup.models import BaseModel
    ...
    >>> class MyTable(BaseModel):
    ...
    ...     table_pk: Mapped[int] = mapped_column(primary_key=True)
    ...     my_column: Mapped[str] = mapped_column()

.. testcleanup::

    BaseModel.metadata.clear()
    BaseModel.registry.dispose()

"""

from sqlalchemy.orm import DeclarativeBase, Mapped, declared_attr

from routeup.core import utils


class BaseModel(DeclarativeBase):
    """Base class for SQLAlchemy ORMs.

    We define the database default table names as the
    ORM Class names converted from ``CamelCase`` to ``snake_case`` with
    :func:`~routeup.core.utils.camel_to_snake_case`.
    For instance an ORM named ``CustomerData`` will map a database table
    named ``customer_data``.

    We also define a common field for every database table: ``id``.
    It defaults to an auto-incrementing integer, which acts as an
    indexed primary key.
    """

    @declared_attr
    def __tablename__(cls) -> Mapped[str]:
        """Automatically define the database table name."""
        return utils.camel_to_snake_case(cls.__name__)


__all__ = ["BaseModel"]
