#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Custom SQLAlchemy type definitions**.

This module contains custom types based on :mod:`sqlalchemy.types`.

"""

from pathlib import Path
from uuid import UUID

from sqlalchemy import util
from sqlalchemy.dialects import mssql, postgresql
from sqlalchemy.engine import Dialect
from sqlalchemy.types import CHAR, TypeDecorator


class UUIDSQLType(TypeDecorator):
    """Platform-independent UUID type.

    Uses a backend-native UUID type when available, and falls back
    to either a CHAR(32) when a native UUID type is not available.

    This allows us to use Python's :class:`~uuid.UUID` in our Python
    code, which is handled nicely by SQLAlchemy to manage the actual
    values in the database backend, whichever it is.

    .. doctest::

        >>> from uuid import UUID

        >>> from sqlalchemy.orm import Mapped, mapped_column

        >>> from routeup.core.types.sqlalchemy import (
        ...     UUIDSQLType
        ... )
        >>> from routeup.models import BaseModel

        >>> class DemoTable(BaseModel):
        ...     demo_column: Mapped[UUID] = mapped_column(
        ...         "demo_column", UUIDSQLType, primary_key=True
        ...     )

    """

    impl = CHAR(32)
    """Let ``impl`` be :class:`sqlalchemy.types.CHAR`
    as a placeholder.

    This assignment has no impact since we are defining a
    ``load_dialect_impl`` method to dynamically load ``impl``
    based on the dialect.

    """
    python_type = UUID
    """Python type representing the SQLAlchemy type."""
    cache_ok = True
    """Statements using this :class:`.UUIDSQLType` are "safe to cache".

    We can be sure about this because the class is stateless,
    we are not defining any ``__init__`` method.

    """

    def __repr__(self):
        """Use SQLAlchemy's __repr__ method."""
        return util.generic_repr(self)

    def load_dialect_impl(self, dialect: Dialect):
        """Load different type depending on the dialect."""
        match dialect.name:
            case mssql.dialect.name:
                return dialect.type_descriptor(mssql.UNIQUEIDENTIFIER())
            case postgresql.dialect.name:
                return dialect.type_descriptor(postgresql.UUID())
            case _:
                return dialect.type_descriptor(self.impl)

    @staticmethod
    def _coerce(value):
        """Coerce method."""
        if value and not isinstance(value, UUID):
            try:
                value = UUID(value)
            except (TypeError, ValueError):
                value = UUID(bytes=value)
        return value

    def process_literal_param(self, value, dialect: Dialect):
        """Render a literal parameter inline within a statement."""
        match value:
            case None:
                return value
            case mssql.dialect.name | postgresql.dialect.name:
                return f"{value}"
            case _:
                return f"{value}"

    def process_bind_param(self, value, dialect: Dialect):
        """Convert a bound parameter to its representation.

        Depending on the backend, :class:`.UUIDSQLType` types are
        stored as :class:`.CHAR` types containing its hex
        representation, or they are stored as specific backend UUID
        types.

        Here we convert an incoming bound parameter so that the
        comparisons made on statements are fully aware of the dialect.

        """
        if value is None:
            return value
        if not isinstance(value, UUID):
            value = self._coerce(value)
        if dialect.name in (mssql.dialect.name, postgresql.dialect.name):
            return str(value)
        return value.hex

    def process_result_value(
        self, value: str | UUID | None, dialect: Dialect
    ) -> UUID | None:
        """Cast a result row value to :class:`~uuid.UUID`."""
        if value is None:
            return value
        if isinstance(value, UUID):
            return value
        return UUID(value)


class PathSQLType(TypeDecorator):
    """Platform-independent Path type.

    Uses a backend-native Path type when available, and falls back
    to either a CHAR(256) when a native Path type is not available.

    This allows us to use Python's :class:`~pathlib.Path` in our Python
    code, which is handled nicely by SQLAlchemy to manage the actual
    values in the database backend, whichever it is.

    .. doctest::

        >>> from pathlib import Path

        >>> from sqlalchemy.orm import Mapped, mapped_column

        >>> from routeup.core.types.sqlalchemy import (
        ...     PathSQLType
        ... )
        >>> from routeup.models import BaseModel

        >>> class PathDemoTable(BaseModel):
        ...     id: Mapped[int] = mapped_column(
        ...         primary_key=True, autoincrement=True
        ...     )
        ...     path_demo_column: Mapped[Path] = mapped_column(
        ...         "path_demo_column", PathSQLType
        ...     )

    """

    impl = CHAR(256)
    """Let ``impl`` be :class:`sqlalchemy.types.CHAR`
    as a placeholder.

    This assignment has no impact since we are defining a
    ``load_dialect_impl`` method to dynamically load ``impl``
    based on the dialect.

    """
    python_type = Path
    """Python type representing the SQLAlchemy type."""
    cache_ok = True
    """Statements using this :class:`.PathSQLType` are "safe to cache".

    We can be sure about this because the class is stateless,
    we are not defining any ``__init__`` method.

    """

    def __repr__(self):
        """Use SQLAlchemy's __repr__ method."""
        return util.generic_repr(self)

    @staticmethod
    def _coerce(value):
        """Coerce method."""
        if value and not isinstance(value, Path):
            value = Path(value)
        return value

    def process_literal_param(self, value, dialect: Dialect):
        """Render a literal parameter inline within a statement."""
        if not value:
            return value
        else:
            return str(value)

    def process_bind_param(self, value, dialect: Dialect):
        """Convert a bound parameter to its representation.

        :class:`.PathSQLType` types are
        stored as :class:`.CHAR` types containing its absolute path
        representation.

        Here we convert an incoming bound parameter independently
        of the dialect.

        """
        if value is None:
            return value
        if not isinstance(value, Path):
            value = self._coerce(value)
        return str(value)

    def process_result_value(
        self, value: str | Path | None, dialect: Dialect
    ) -> Path | None:
        """Cast a result row value to :class:`~pathlib.Path`."""
        if value is None:
            return value
        if isinstance(value, Path):
            return value
        return Path(value.strip())


__all__ = ["UUIDSQLType", "PathSQLType"]
