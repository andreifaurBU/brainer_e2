#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Custom exceptions**.

Contains definitions for any custom exception raised
by ``routeup``.

Exceptions should consistently inherit the Python Language native ones.
This is, if you write a custom exception for an argument error to
one of your package functions, it should inherit from :exc:`ValueError`.

"""


class NotExistingJSONSchema(KeyError):
    """Error raised when a json schema does not exist."""

    def __init__(self, msg):
        """Set error instance parameters."""
        self.msg = msg


class TableCreationTimeout(KeyError):
    """Error raised when a json schema does not exist."""

    def __init__(self, msg):
        """Set error instance parameters."""
        self.msg = msg


class MissingAPIKey(ValueError):
    """Error raised when no API key is provided."""

    pass


class InvalidResponseFormat(Exception):
    """Error raised when the response format is invalid."""

    pass


class InvalidRequestFormat(Exception):
    """Error raised when the response format is invalid."""

    pass


class CapacityError(ValueError):
    """Error raised when the demands are larger than the capacity."""

    pass


class RepeatedStopsError(ValueError):
    """Error raised when there are repeated stop ids in the request."""

    pass


class NoRegionFoundError(ValueError):
    """Error raised when there is no region for the given coords."""

    pass
