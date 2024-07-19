#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**SQLAlchemy ORMs**.

Contains the `SQLAlchemy ORM <https://docs.sqlalchemy.org/en/20/orm/>`_
database models used in the project.

We organize this subpackage with **one file per database table**,
containing the ORM class that maps it.

There is one special module called :mod:`.base` that holds the base
class all ORMs defined within this package should inherit from.

Note that `Alembic <https://alembic.sqlalchemy.org/en/latest/>`_
imports the :class:`~routeup.models.base.BaseModel`
from this module, and therefore
any database table you need to be handled with Alembic should be
imported here and added to ``__all__``, so that when importing the
base class the table becomes attached to it.

"""

from routeup.models.base import BaseModel

__all__ = ["BaseModel"]
