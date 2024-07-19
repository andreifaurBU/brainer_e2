#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Region pandera schemas."""

from pandera import Field
from pandera.typing import Series

from routeup.schemas_df.base import BaseDFSchema


class RegionDFSchema(BaseDFSchema):
    """Schema of the table response."""

    name: Series[str] = Field(nullable=False)
    geometry: Series[str] = Field(nullable=False)
