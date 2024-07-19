#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Custom Pydantic type definitions**.

This module contains custom types based on
`Pydantic <https://pydantic-docs.helpmanual.io/>`_.

"""

import re
from typing import TYPE_CHECKING, Generator, Pattern

from pydantic import AfterValidator
from pydantic_core import Url
from typing_extensions import Annotated

if TYPE_CHECKING:  # pragma: nocover
    from pydantic.typing import AnyCallable

    CallableGenerator = Generator[AnyCallable, None, None]

from pydantic.networks import UrlConstraints  # type: ignore
from pydantic.v1.errors import (
    UrlExtraError,
)

SQLITE_DSN_SCHEMES = ["sqlite"]

_sqlite_uri_regex_cache = None


def sqlite_uri_regex() -> Pattern[str]:
    """Regular expression for SQLite URIs."""
    global _sqlite_uri_regex_cache
    if _sqlite_uri_regex_cache is None:
        _sqlite_uri_regex_cache = re.compile(
            r"(?:(?P<scheme>[a-z][a-z0-9+\-.]+)://)?"  # scheme
            r"(?P<host>/)?"  # host needs to be '/'
            r"(?P<path>[^\s?#]*)?"  # path
            r"(?:\?(?P<query>[^\s#]+))?",  # query
            re.IGNORECASE,
        )
    return _sqlite_uri_regex_cache


def validate(value):
    """Validates the SQLite URI."""
    m = sqlite_uri_regex().match(str(value))
    assert m, "URL regex failed unexpectedly"

    if m.end() != len(str(value)):
        raise UrlExtraError(extra=value[m.end() :])

    return value


SQLiteDsn = Annotated[
    Url,
    UrlConstraints(
        max_length=2**16,
        allowed_schemes=SQLITE_DSN_SCHEMES,
    ),
    AfterValidator(validate),
]
