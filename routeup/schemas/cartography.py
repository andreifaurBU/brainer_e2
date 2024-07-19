#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Valhalla schemas."""

import numpy as np
from pydantic import Field, field_validator, model_validator
from pydantic_extra_types.coordinate import Latitude, Longitude

from .base import BaseSchema


class CartographyRoute(BaseSchema):
    """Valhalla route schema."""

    coordinates: list[tuple[float, float]]
    times: list[float] = Field(default_factory=list)
    distances: list[float] = Field(default_factory=list)

    @field_validator("times", "distances")
    def no_null_values(cls, value: list[float]) -> list[float]:
        """Ensures no null values exist in the list."""
        if any(np.isnan(val) for val in value):
            raise ValueError("Values in the list cannot be null.")
        return value

    @model_validator(mode="after")
    def check_lengths(self, data):
        """Ensures the lengths and times lists have the same length."""
        if len(self.times) != len(self.distances):
            raise ValueError("Times and lengths must have the same length")
        return data


class RequestStopGoogle(BaseSchema):
    """A dataclass to represent a stop in a route request."""

    lat: Latitude
    lng: Longitude


class RequestStopValhalla(BaseSchema):
    """A dataclass to represent a stop in a route request."""

    lat: Latitude
    lon: Longitude
