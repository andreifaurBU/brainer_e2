#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Project configuration**.

This module holds both application and environmental configurations.

We use Pydantic's
`Settings <https://pydantic-docs.helpmanual.io/usage/settings/>`_
to read and parse configurations from the environment, and
`DriConfig <https://dribia.github.io/driconfig/>`_ to read and parse
YAML application configurations.

    - Application configurations:
        - Are defined as attributes in :class:`~.config.AppConfig`.
        - Do not depend on the environment.
        - Only contain nonsensible information (e.g. model parameters).
        - Are read from a YAML config file (not from the environment).
    - Environment configurations:
        - Are defined as attributes in :class:`~.config.GlobalConfig`
          or its subclasses (e.g. :class:`~.config.DevConfig`).
        - Can be environment-dependant.
        - May contain sensible information such as usernames,
          passwords or URLs to customer resources.
        - Can be loaded from a ``.env`` file in the root
          directory of the project, which **must not be added to VCS**.
          Alternatively, they can be read from environment variables.

The :obj:`.config` object should be used throughout the code to access
configuration values.

If you want to organize your application configurations into different
YAML files, you should create one interface per file, mimicking the
definition of :class:`.AppConfig`.

Finally, to override any of the environmental configuration defined in
:class:`.GlobalConfig` or its subclasses, an environment variable can
be defined in the OS, with the same name of the configuration defined
in the config class, and prefixed with the :obj:`.ENV_PREFIX`.

"""

from pathlib import Path
from typing import Any, Type

from driconfig import DriConfig, DriConfigConfigDict
from pydantic import DirectoryPath, Field
from pydantic.functional_validators import field_validator
from pydantic.type_adapter import TypeAdapter
from pydantic_core.core_schema import ValidationInfo
from pydantic_settings import (
    BaseSettings,
    PydanticBaseSettingsSource,
    SettingsConfigDict,
)
from typing_extensions import Annotated

from ..types.pydantic import SQLiteDsn
from .enums import DistanceMetric, Environment, LogLevel
from .interfaces import LoggerOptions

ENV_PREFIX = "ROUTEUP"
"""Environment variables prefix for project configurations.

Environment variables can be used to override any of the configurations
defined in :class:`~.config.GlobalConfig` or its subclasses.
However, to reduce the chance of having repeated variable names within
different projects, we need to add a prefix to the variable names in
order to be taken into account when building the project's
configuration.

For instance, to override the :attr:`~.config.GlobalConfig.ENV`
attribute with an environment variable, we should define:

.. code-block:: console

    $ export ROUTEUP_ENV=PRO

"""


class AppConfig(DriConfig):
    """**Application configurations interface**.

    This class is an interface with the project's ``config.yaml``
    configuration file.

    Interfaces representing each of the YAML file sections can be
    defined as Pydantic models in :mod:`~.config.interfaces`.

    When adding configurations in the ``config.yaml`` config file one
    should define the corresponding attribute on this model so that
    the new configuration can be accessed from code.

    Let's say we add the following section to our config file:

    .. code-block:: yaml

        TABLE_NAMES:
            - USERS:
                NAME: users
                KEY: user_id
            - ITEMS:
                NAME: items
                KEY: item_id

    At this point we can choose how deeply we define the model for our
    new configuration. Starting with the simplest definition, we could
    do:

    ::

        from typing import Any

        class AppConfig(DriConfig):

            ...

            TABLE_NAMES: list[Any]

    We could also give more detail about the structure of the new
    YAML section:

    ::

        from typing import Any

        class AppConfig(DriConfig):

            ...

            TABLE_NAMES: list[dict[str, dict[str, str]]]

    And finally (and recommended), we could write models to interface
    our new config section:

    ::

        # routeup.core.config.interfaces.py

        class TableConfig(BaseInterface):

            NAME: str
            KEY: str

        class TableNames(BaseInterface):

            USERS: TableConfig
            ITEMS: TableConfig

    And add it as an attribute of :class:`.AppConfig`:

    ::

        from .interfaces import TableNames

        class AppConfig(DriConfig):

            ...

            TABLE_NAMES: TableNames

    With whichever option we have chosen, now we should be able to
    access the new config section in the :obj:`.config` object:

    ::

        from routeup.core import config

        config.APP_CONFIG.TABLE_NAMES

    """

    def __init__(self):
        """This class has no initialization parameters.

        :meta private:
        """
        super().__init__()

    model_config = DriConfigConfigDict(
        config_folder=Path(__file__).parents[2] / "config",
        config_file_name="config.yaml",
    )

    LOGGER_OPTIONS: LoggerOptions
    """Options to pass to RouteUp's logger
    object on creation.
    """
    grid_search_time_limit: int
    """Time limit for grid search in seconds."""


class GlobalConfig(BaseSettings):
    """**Environment configurations interface**.

    This class contains every environment-dependant configuration
    of the project. A few examples could be:

    * Sensible information such as usernames or passwords.
    * Path routes that might change depending on the environment.
    * Configurations that would be useful to be changed depending
      on the environment, such as the application's logging level.

    In order to add a configuration value, simply add it as an
    attribute to this class, with its corresponding type and a
    default value, if desired.

    If the default value is not informed it means that the
    configuration value is *required*. This class will search
    for the value in:

    * An ``.env`` file located at the root folder of the project.
    * An OS environment variable defined with the same name of the
      configuration, and prefixed with :obj:`.ENV_PREFIX`.

    If none of the above is found and the configuration value is
    required, an exception will be raised.

    """

    model_config = SettingsConfigDict(
        case_sensitive=True,
        env_file=Path(__file__).parents[3] / ".env",
        env_prefix=f"{ENV_PREFIX}_",
        extra="ignore",
    )

    @classmethod
    def settings_customise_sources(
        cls,
        settings_cls: Type[BaseSettings],
        init_settings: PydanticBaseSettingsSource,
        env_settings: PydanticBaseSettingsSource,
        dotenv_settings: PydanticBaseSettingsSource,
        file_secret_settings: PydanticBaseSettingsSource,
    ) -> tuple[PydanticBaseSettingsSource, ...]:
        """Modify sources preference wrt Pydantic defaults."""
        return env_settings, dotenv_settings, init_settings, file_secret_settings

    APP_CONFIG: AppConfig = AppConfig()
    """Application configuration object containing the attributes read
    from the YAML config file.
    """

    ENV: Environment = Environment.DEV
    """Application environment."""

    PATH_DATA: DirectoryPath = Path(__file__).parents[3] / "data"
    """Path to the data folder."""

    PATH_DATA_DB: Path = Path("db")
    """Path to the db data sub-folder."""
    PATH_DATA_EXTERNAL: Path = Path("external")
    """Path to the external data sub-folder."""
    PATH_DATA_INTERIM: Path = Path("interim")
    """Path to the interim data sub-folder."""
    PATH_DATA_LOG: Path = Path("log")
    """Path to the log data sub-folder."""
    PATH_DATA_MODELS: Path = Path("models")
    """Path to the models sub-folder."""
    PATH_DATA_PROCESSED: Path = Path("processed")
    """Path to the processed data sub-folder."""
    PATH_DATA_RAW: Path = Path("raw")
    """Path to the raw data sub-folder."""
    PATH_DATA_RESULTS: Path = Path("results")
    """Path to the results data sub-folder."""

    @field_validator(
        "PATH_DATA_DB",
        "PATH_DATA_EXTERNAL",
        "PATH_DATA_INTERIM",
        "PATH_DATA_LOG",
        "PATH_DATA_MODELS",
        "PATH_DATA_PROCESSED",
        "PATH_DATA_RAW",
        "PATH_DATA_RESULTS",
    )
    def dynamic_on_data_path(cls, v, info: ValidationInfo):
        """Make data sub-folders dynamic on the data folder value."""
        if v == cls.model_fields[info.field_name].default and "PATH_DATA" in info.data:
            return TypeAdapter(DirectoryPath).validate_python(
                info.data["PATH_DATA"] / v
            )
            # return DirectoryPath.validate(info.data["PATH_DATA"] / v)
        return TypeAdapter(DirectoryPath).validate_python(v)
        # return DirectoryPath.validate(v)

    LOG_LEVEL: LogLevel = LogLevel.INFO
    """Logging level."""
    LOG_FILENAME: str = f"{ENV_PREFIX.lower()}.log"
    """Logging filename, which will be placed in
    :attr:`~.PATH_DATA_LOG`
    """

    DATABASE_NAME: str = ENV_PREFIX
    """Database name."""
    DATABASE_PATH: Path | None = None
    """Database path."""
    DATABASE_URI: Annotated[SQLiteDsn | None, Field(validate_default=True)] = None
    """Database URI."""

    @field_validator("DATABASE_PATH")
    def dynamic_on_data_db_path(cls, v: Path | None, info: ValidationInfo) -> Path:
        """Make the DB path dynamic on the data/db folder."""
        if v is not None:
            return v
        return info.data.get("PATH_DATA_DB", Path(".")) / info.data["DATABASE_NAME"]

    @field_validator("DATABASE_URI")
    def assemble_db_connection(cls, v: str | None, info: ValidationInfo) -> Any:
        """Assemble the database connection URI."""
        return (
            v
            if v
            else SQLiteDsn.build(
                scheme="sqlite",
                host="/",
                path=str(info.data["DATABASE_PATH"].absolute()),
            )
        )

    PATH_JSON_SCHEMA: Path = Path(__file__).parents[3] / "schemas_json"
    """Path to the JSON schema folder."""
    GCLOUD_PROJECT_ID: str = "brainer-390415"
    """BigQuery project ID."""
    GCLOUD_DATASET_ANALYTICS: str = "routeup"
    """BigQuery Analytics dataset name."""
    GCLOUD_CREDENTIALS_PATH: Path = (
        Path(__file__).parents[3] / "gcloud_brainer_credentials.json"
    )
    """BigQuery Analytics dataset name."""
    # BIGQUERY_HTTP: str | None = None
    # """BigQuery _http."""
    VALHALLA_API_URL: str = "http://localhost:8002/"
    """Valhalla API URL."""
    GOOGLE_MAPS_API_KEY: str = ""
    """Google Maps API key."""
    DISTANCE_METRIC: str = DistanceMetric("meters")
    """Distance metric."""
    MAX_VEHICLES: int = 100
    """Max vehicles that can be introduced in a request."""
    MAX_VEHICLE_CAPACITY: int = 100
    """Max vehicle capacity that can be introduced in a request."""


class DevConfig(GlobalConfig):
    """Configuration overrides for the DEV environment."""

    model_config = SettingsConfigDict(
        env_prefix=f"{ENV_PREFIX}_{Environment.DEV.name}_",
    )

    LOG_LEVEL: LogLevel = LogLevel.DEBUG
    """The log level is set to :attr:`~.config.enums.LogLevel.DEBUG`
    in the :attr:`~.config.enums.Environment.DEV` environment.
    """


class ProConfig(GlobalConfig):
    """Configuration overrides for the PRO environment."""

    LOG_LEVEL: LogLevel = LogLevel.INFO
    """The log level is set to :attr:`~.config.enums.LogLevel.INFO`
    in the :attr:`~.config.enums.Environment.PRO` environment.
    """


class PreConfig(GlobalConfig):
    """Configuration overrides for the PRO environment."""

    LOG_LEVEL: LogLevel = LogLevel.DEBUG
    """The log level is set to :attr:`~.config.enums.LogLevel.INFO`
    in the :attr:`~.config.enums.Environment.PRO` environment.
    """


class UnitTestConfig(GlobalConfig):
    """Configuration overrides for the UNITTEST environment."""

    LOG_LEVEL: LogLevel = LogLevel.TRACE
    """The log level is set to :attr:`~.config.enums.LogLevel.TRACE`
    in the :attr:`~.config.enums.Environment.UNITTEST` environment.
    """
    DATABASE_URI: SQLiteDsn = SQLiteDsn.build(scheme="sqlite", host="", path=":memory:")
    """We use an in-memory SQLite database in the
    :attr:`~.config.enums.Environment.PRO` environment.
    """
    # BIGQUERY_HTTP: str = ""


class FactoryConfig:
    """**Configuration factory**.

    Returns a config instance depending on the ENV variable.

    """

    def __init__(self, env: Environment):
        """Configuration factory parameters.

        The configuration factory depends only on the defined
        environment.

        Args:
            env: Environment.

        """
        self.env: Environment = env

    def __call__(self, **values):
        """Instances of the factory config are callable.

        Every keyword parameter passed when calling the instance
        will be used as an initialization parameter of the
        corresponding configuration class instance.

        Args:
            **values: Keyword arguments for the config instance.

        Returns: The configuration class instance of the corresponding
          environment.

        """
        match self.env:
            case Environment.DEV:
                return DevConfig(**values)
            case Environment.PRO:
                return ProConfig(**values)
            case Environment.UNITTEST:
                return UnitTestConfig(**values)
            case Environment.PRE:
                return PreConfig(**values)
            case _:
                raise RuntimeError("Environment not recognized.")


config = FactoryConfig(GlobalConfig().ENV)(
    **GlobalConfig().model_dump(exclude_unset=True)
)
"""Configuration object to be used anywhere in the project's code.

It contains both the application and the environment configurations:

.. testsetup::

    from routeup.core.config import AppConfig
    from routeup.core.config import GlobalConfig

.. doctest::

    >>> from routeup.core import config
    ...
    >>> isinstance(config, GlobalConfig)
    True
    >>> isinstance(config.APP_CONFIG, AppConfig)
    True

:meta hide-value:
"""
