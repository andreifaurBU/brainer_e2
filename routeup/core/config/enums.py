#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Configuration enumerations**.

This module holds any enumeration used either in the configuration
classes or throughout the code. Classes defined in this module should
inherit from Python's :class:`~enum.Enum` builtin enumeration.

"""

from enum import Enum


class Environment(str, Enum):
    """Environment options.

    We define here the available environments and their names.
    When defining a new environment, we should define also a new
    config class, to be able to override configurations.

    Use this enumerator to check the environment in any part of
    the code:

    .. doctest::

        >>> from routeup.core import config
        >>> from routeup.core.config import Environment

        >>> if config.ENV is Environment.DEV:
        ...     # Do something only in DEV.
        ...     pass
        ... elif config.ENV is Environment.PRO:
        ...     # Do something only in PRO.
        ...     pass

    """

    DEV = "DEV"
    """Local development environment."""
    PRO = "PRO"
    """Production environment."""
    PRE = "PRE"
    """Pre-production environment."""
    UNITTEST = "UNITTEST"
    """Special unit testing environment."""


class LogLevel(str, Enum):
    """Log level options.

    Use this enumerator to set the level of a given logging message:

    .. doctest::

        >>> from routeup.core import logger
        >>> from routeup.core.config import LogLevel

        >>> # This will produce an INFO message.
        >>> logger.log(LogLevel.INFO, "My info message.")
        INFO       | My info message.
    """

    TRACE = "TRACE"
    """Designates finer-grained informational events than the
    :attr:`~.DEBUG`."""
    DEBUG = "DEBUG"
    """Designates fine-grained informational events that are most
    useful to debug an application.
    """
    INFO = "INFO"
    """Informational messages that highlight the progress of the
    application at coarse-grained level.
    """
    SUCCESS = "SUCCESS"
    """Designates important successful events such as a long-running
    complicated process finishing without errors.
    """
    WARNING = "WARNING"
    """Designates potentially harmful situations."""
    ERROR = "ERROR"
    """Designates error events that might still allow the application
    to continue running.
    """
    CRITICAL = "CRITICAL"
    """Designates very severe error events that will presumably lead
    the application to abort.
    """


class RouteMode(str, Enum):
    """Enum class for the mode of the optimizer."""

    INBOUND = "inbound"
    OUTBOUND = "outbound"


class CartographyProvider(str, Enum):
    """Enum class for the cartography providers."""

    GOOGLE = "google"
    VALHALLA = "valhalla"


class DistanceMetric(str, Enum):
    """Enum class for the distance metrics."""

    METERS = "meters"
    KILOMETERS = "kilometers"
