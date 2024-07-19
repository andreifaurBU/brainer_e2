#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**CRUD operations package**.

Every CRUD (Create, Read, Update and Delete) operation should be coded
within this package.

We organize this subpackage with **one file per database table**,
containing the CRUD class used to interact with it. This CRUD class
contains every CRUD operation that is done within the project against
such database table.

There is one special module called :mod:`.crud.base` that holds the
base class all CRUD classes defined within this package should inherit
from.

We make use of `SQLAlchemy <https://docs.sqlalchemy.org/en/14/>`_
both to map our tables with
`ORMs <https://docs.sqlalchemy.org/en/14/orm/index.html>`_ and to
write `SQL operations
<https://docs.sqlalchemy.org/en/14/tutorial/data.html>`_.

Let's say we have a ``MyTable`` table in our database and want to
create a new CRUD class to interact with it:

.. doctest::

    # routeup.models.my_table.py

    >>> from sqlalchemy.orm import Mapped, mapped_column
    >>> from routeup.models import BaseModel
    ...
    >>> class MyTable(BaseModel):
    ...
    ...     table_pk: Mapped[int] = mapped_column(primary_key=True)
    ...     name: Mapped[str] = mapped_column()

First, we will need to create both a Schema and a DFSchema representing
a row and the full data table respectively:

.. doctest::

    # routeup.schemas.my_table.py

    >>> from routeup.schemas.base import BaseSchema
    ...
    >>> class MyTableSchema(BaseSchema):
    ...
    ...     name: str

.. doctest::

    # routeup.schemas_df.my_table.py

    >>> from pandera import String
    >>> from pandera.typing import Series
    >>> from routeup.schemas_df.base import (
    ...     BaseDFSchema
    ... )
    ...
    >>> class MyTableDFSchema(BaseDFSchema):
    ...
    ...     name: Series[str]

Now we can create the new CRUD class inheriting from our
:class:`~.crud.base.CRUDBase` base class:

.. doctest::

    # routeup.crud.my_table.py

    >>> from routeup.crud.base import CRUDBase
    ...
    >>> class CRUDMyTable(
    ...     CRUDBase[
    ...         MyTable,
    ...         MyTableSchema,
    ...         MyTableSchema,
    ...         MyTableDFSchema
    ...     ]
    ... ):
    ...     pass
    ...
    >>> my_table = CRUDMyTable(MyTable, MyTableDFSchema)

.. testcleanup::

    BaseModel.metadata.clear()
    BaseModel.registry.dispose()

"""
