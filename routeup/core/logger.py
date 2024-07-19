#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Logging module**.

Contains the logger object that should be used to log messages
throughout the project's code. Here, instead of using Python's
builtin :mod:`logging`, we use :class:`~loguru._logger.Logger`.

The :obj:`.logger` object can be imported from wherever in the
project's code to emit log messages:

.. doctest::

    >>> from routeup.core import logger

    >>> # This will produce an INFO message
    >>> logger.info("My informative message.")
    INFO       | My informative message.

    >>> # This will produce a DEBUG message
    >>> logger.debug("My debugging message.")
    DEBUG      | My debugging message.

Alternatively, one can also make use of the :class:`.config.LogLevel`
enumerator:

.. doctest::

    >>> from routeup.core import logger
    >>> from routeup.core.config import LogLevel

    >>> # This will produce an INFO message
    >>> logger.log(LogLevel.INFO, "My informative message.")
    INFO       | My informative message.

    >>> # This will produce an DEBUG message
    >>> logger.log(LogLevel.DEBUG, "My debugging message.")
    DEBUG      | My debugging message.

"""

import sys

from loguru import logger

from .config import config

logger.remove()

logger.add(
    config.PATH_DATA_LOG / config.LOG_FILENAME,
    format=config.APP_CONFIG.LOGGER_OPTIONS.LOG_FORMAT_F,
    level=config.LOG_LEVEL,
    rotation=config.APP_CONFIG.LOGGER_OPTIONS.MAX_BYTES,
    retention=config.APP_CONFIG.LOGGER_OPTIONS.MAX_DAYS,
    colorize=False,
)

logger.add(
    sink=sys.stdout,
    format=config.APP_CONFIG.LOGGER_OPTIONS.LOG_FORMAT_T,
    level=config.LOG_LEVEL,
    colorize=True,
)

__all__ = ["logger"]
