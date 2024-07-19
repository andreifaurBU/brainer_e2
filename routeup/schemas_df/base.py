#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Base classes for Pandera DataFrame schemas**.

Here we define the base dataframe schemas that feed schemas created
within this package.

"""

from pandera import SchemaModel


class BaseDFSchema(SchemaModel):
    """Base dataframe schema.

    Every Pandera dataframe schema inherits this base, and therefore
    inherits its configuration.

    """

    class Config:
        """Pandera dataframe schemas configuration."""

        coerce = True
        """Coerce types when possible instead of raising a validation
        error."""


__all__ = ["BaseDFSchema"]
