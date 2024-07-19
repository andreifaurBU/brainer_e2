#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**YAML configurations interfaces**.

App configurations are listed in YAML files which can be located
inside the ``routeup/config/`` folder.
These YAML files are parsed in the :mod:`~.core.config`
module using DriConfig.

`DriConfig <https://dribia.github.io/driconfig/>`_ is based on
`Pydantic <https://pydantic-docs.helpmanual.io/>`_,
and allows defining Pydantic schemas to parse YAML configurations.
This module is intended to contain all
those Pydantic schemas used as an interface with our YAML files.

For instance, the :class:`.LoggerOptions` class is intended to parse
the following YAML section of our config file:

.. code-block:: yaml

    LOGGER_OPTIONS:
        MAX_BYTES: 20971520  # 20MB
        MAX_DAYS: 365  # 1Y
        LOG_FORMAT_F: "{time} - {level} - {function} - {message}"
        LOG_FORMAT_T: "<green>{time}</green> - {level} - {message}"

.. doctest::

    >>> from routeup.core import config
    >>> logger_options = config.APP_CONFIG.LOGGER_OPTIONS
    >>> print(logger_options) # doctest: +NORMALIZE_WHITESPACE
    MAX_BYTES=20971520 MAX_DAYS=365 LOG_FORMAT_F='{time}
    ({elapsed}) - {level} - {module} - {function} - {message}'
    LOG_FORMAT_T='<green>{time:HH:mm:ss} - ({elapsed})</green> |
    <level>{level: <10}</level> |
    <cyan>{name}</cyan>:<cyan>{line}</cyan> |
    <level>{message}</level>' LOG_FORMAT_U='{level: <10} | {message}'
"""

from pydantic import BaseModel
from pydantic_settings import SettingsConfigDict


class BaseInterface(BaseModel):
    """Base Pydantic schema for a YAML interface.

    We only need to override the ``validate_all`` configuration of the
    base model so that the DriConfig class validates all the fields of
    our model.

    """

    model_config = SettingsConfigDict(
        validate_default=True,  # Validate field defaults.
    )


class LoggerOptions(BaseInterface):
    """Interface for the ``LOGGER_OPTIONS`` configuration.

    The configurations defined by this interface are used in the
    :mod:`~.core.logger` module to define
    ``routeup``'s logging object.

    """

    MAX_BYTES: int
    """Maximum number of bytes of the logging file."""
    MAX_DAYS: int
    """Maximum number of days we keep a logging file."""
    LOG_FORMAT_F: str
    """The logging messages format used in the file log sink."""
    LOG_FORMAT_T: str
    """The logging messages format used in the terminal log sink."""
    LOG_FORMAT_U: str
    """The logging messages format used in unit testing."""
