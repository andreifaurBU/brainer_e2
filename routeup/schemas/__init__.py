#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Pydantic data schemas**.

Contains the `Pydantic <https://pydantic-docs.helpmanual.io/>`_ data
schemas used in the project.

Data schemas are used, for instance, to interact with the database
via CRUD operations. In this case, we organize schemas in modules
named after the database table. In other cases where schemas are not
related with DB tables, we organize them by *concept*.

There is one special module called :mod:`.base` that holds the base
class all schemas defined within this package should inherit from.

"""
from .cartography import CartographyRoute
from .route_optimizer import GridSearchOutput, RouteOptimizerInput, RouteOptimizerOutput

__all__ = [
    "CartographyRoute",
    "RouteOptimizerInput",
    "RouteOptimizerOutput",
    "GridSearchOutput",
]
