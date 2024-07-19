#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Cartography API."""
from pydantic_extra_types.coordinate import Coordinate

from routeup import schemas
from routeup.core.config.enums import CartographyProvider
from routeup.data.client.gmaps import GoogleMaps  #

# from routeup.data.client import GoogleMaps, ValhallaClient
from routeup.data.client.utils import MatrixResult
from routeup.data.client.valhalla import ValhallaClient


class CartographyAPI:
    """Cartography API."""

    def __init__(self, provider: CartographyProvider, base_url: str = None):
        """Cartography API initialization."""
        self.client: GoogleMaps | ValhallaClient | None = None
        if provider == CartographyProvider.VALHALLA:
            if base_url:
                self.client = ValhallaClient(base_url=base_url)
            else:
                raise ValueError("base_url is required for Valhalla")
        elif provider == CartographyProvider.GOOGLE:
            self.client = GoogleMaps()
        else:
            raise ValueError("provider not available")

    def get_matrix(self, stops: list[Coordinate], costing: str = None) -> MatrixResult:
        """Get matrix."""
        return self.client.get_matrix(stops=stops, costing=costing)

    def get_route(
        self, stops: list[Coordinate], costing: str = None
    ) -> schemas.CartographyRoute:
        """Get route."""
        return self.client.get_route(stops=stops, costing=costing)
