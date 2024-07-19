#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Response pandera schemas."""

from pandera import Field, Timestamp
from pandera.typing import INT64, Series

from routeup.schemas_df.base import BaseDFSchema


class RouteOptimizerDFSchema(BaseDFSchema):
    """Schema of the table response."""

    id: Series[int] = Field(nullable=False)
    task_id: Series[str] = Field(nullable=True)
    event: Series[str] = Field(nullable=False)
    status: Series[str] = Field(nullable=True)
    error_log: Series[str] = Field(nullable=True)
    created: Series[Timestamp] = Field(nullable=False)
    updated: Series[Timestamp] = Field(nullable=True)
    owner_id: Series[INT64] = Field(nullable=False)
    input: Series[str] = Field(nullable=False)
    output: Series[str] = Field(nullable=True)
    solution_found: Series[bool] = Field(nullable=True)
