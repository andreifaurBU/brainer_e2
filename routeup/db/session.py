#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Database sessions**.

This module provides methods to connect to the database.

The main -and recommended- method is to use the :obj:`.SessionLocal`
session factory.

The :obj:`.SessionLocal` session factory can be used to produce
database :class:`~sqlalchemy.orm.Session` instances,
with which we can interact with the database.

"""
import warnings
from sqlite3 import Connection

from sqlalchemy import create_engine, event
from sqlalchemy.orm import sessionmaker

from routeup.core import config
from routeup.core.types.pydantic import SQLITE_DSN_SCHEMES

# ignore _CLOUD_SDK_CREDENTIALS_WARNING
warnings.filterwarnings(action="ignore", category=UserWarning)


def _fk_pragma_on_connect(dbapi_con: Connection, *_):
    """Enable the foreign keys in SQLite."""
    dbapi_con.execute("pragma foreign_keys=ON")


_engine = create_engine(str(config.DATABASE_URI), pool_pre_ping=True, future=True)
"""SQLAlchemy :class:`~sqlalchemy.engine.Engine` instance.

It is configured with the URI provided by
:attr:`~routeup.core.config.GlobalConfig.DATABASE_URI`.

:meta hide-value:
"""

if config.DATABASE_URI.scheme in SQLITE_DSN_SCHEMES:
    """Activate foreign keys behavior in SQLite."""
    event.listen(_engine, "connect", _fk_pragma_on_connect)

SessionLocal = sessionmaker(
    autocommit=False,
    autoflush=False,
    bind=_engine,
    future=True,
)
"""Factory to produce sessions to the internal database.

This session can be used throughout the project to access the database:

.. doctest::

    >>> from sqlalchemy import select
    >>> from routeup.db.session import SessionLocal

    >>> with SessionLocal() as db:
    ...     result = db.execute(select(1)).scalar()
    >>> result
    1

To access the :class:`~sqlalchemy.engine.Engine` instance bound to the
session, one can do:

.. doctest::

    >>> from routeup.db.session import SessionLocal

    >>> with SessionLocal() as db:
    ...     engine = db.bind
    >>> engine
    Engine(sqlite:///:memory:)

The session is configured to point at the URI provided by
:attr:`~routeup.core.config.GlobalConfig.DATABASE_URI`.

:meta hide-value:
"""

__all__ = ["SessionLocal"]
