#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Google Maps client."""
import googlemaps
import numpy as np
from polyline import polyline
from pydantic_extra_types.coordinate import Coordinate

from routeup import schemas
from routeup.core import Singleton, config, logger
from routeup.core.exceptions import (
    InvalidRequestFormat,
    InvalidResponseFormat,
    MissingAPIKey,
)
from routeup.data.client.utils import MatrixResult, slice_matrix
from routeup.schemas.cartography import RequestStopGoogle


class GoogleMaps(metaclass=Singleton):
    """Google Maps client."""

    def __init__(self, max_route_stops: int = 27, max_matrix_elements: int = 100):
        """Google Maps client.

        Args:
            max_route_stops: Maximum number of stops in a route.
            max_matrix_elements: Maximum number of elements in time/distance matrix.
        """
        if not config.GOOGLE_MAPS_API_KEY:
            raise MissingAPIKey("Google Maps API key not found")
        self.client = googlemaps.Client(key=config.GOOGLE_MAPS_API_KEY)
        self.max_route_stops = max_route_stops
        self.max_matrix_elements = max_matrix_elements
        self._polyline_precision = 5
        if config.DISTANCE_METRIC == "meters":
            self.distance_conversion_factor = 1.0
        elif config.DISTANCE_METRIC == "kilometers":
            self.distance_conversion_factor = 1e-3
        else:
            raise ValueError("Invalid distance metric")

    def _parse_matrix_response(self, response: dict) -> MatrixResult:
        """Parse the response from Google Maps Matrix API.

        Args:
            response: JSON response from the Matrix API.

        Returns:
        - A tuple containing time matrix and distance matrix.
        """
        if "rows" not in response:
            raise InvalidResponseFormat("Invalid response format: 'rows' not found")

        rows = response["rows"]
        num_rows = len(rows)

        if num_rows == 0:
            raise InvalidResponseFormat("No rows in response")

        time_matrix = []
        distance_matrix = []

        for row in rows:
            if "elements" not in row:
                raise InvalidResponseFormat("'elements' not found in a row")

            elements = row["elements"]

            row_time = []
            row_distance = []

            for element in elements:
                if "duration" not in element or "distance" not in element:
                    raise InvalidResponseFormat(
                        "'duration' or 'distance' not found in an element"
                    )

                duration = element["duration"]["value"]
                distance = (
                    element["distance"]["value"] * self.distance_conversion_factor
                )

                row_time.append(duration)
                row_distance.append(distance)

            time_matrix.append(row_time)
            distance_matrix.append(row_distance)

        return MatrixResult(np.array(time_matrix), np.array(distance_matrix))

    def _parse_route_response(self, response: list[dict]) -> schemas.CartographyRoute:
        """Parse the response from the Distance API (e.g., Valhalla).

        Args:
            response: JSON response from the Distance API.

        Returns:
        - Instance of CartographyRoute.
        """
        route = response[0]
        if ("overview_polyline" not in route) or ("legs" not in route):
            raise InvalidResponseFormat(
                "'overview_polyline' or 'legs' not found in response"
            )
        encoded_polyline = route["overview_polyline"]["points"]
        coordinates = polyline.decode(
            encoded_polyline, precision=self._polyline_precision
        )
        try:
            times = [leg["duration"]["value"] for leg in route["legs"]]
            distances = [
                leg["distance"]["value"] * self.distance_conversion_factor
                for leg in route["legs"]
            ]
        except KeyError as e:
            raise InvalidResponseFormat(f"{e}") from e

        return schemas.CartographyRoute(
            coordinates=coordinates, times=times, distances=distances
        )

    @staticmethod
    def _prepare_stops(stops: list[Coordinate]) -> list[dict]:
        return [
            RequestStopGoogle(lat=stop.latitude, lng=stop.longitude).model_dump()
            for stop in stops
        ]

    def _prepare_request_route(self, stops: list[Coordinate], costing: str) -> dict:
        """Prepare request for Google Maps API.

        Args:
            stops (list[Coordinate]): List of stops.
            costing (str): Costing model.
        """
        stops_input = self._prepare_stops(stops)
        if len(stops_input) < 2:
            raise InvalidRequestFormat("At least two stops are required")
        elif len(stops_input) < 3:
            # TODO claudia 2024/04/03 - transform this to pydantic model
            return {
                "origin": stops_input[0],
                "destination": stops_input[1],
                "mode": costing,
            }
        elif (len(stops_input) >= 3) and (len(stops_input) <= self.max_route_stops):
            return {
                "origin": stops_input[0],
                "destination": stops_input[-1],
                "waypoints": stops_input[1:-1],
                "mode": costing,
            }
        else:
            raise InvalidRequestFormat(
                f"Maximum number of stops is {self.max_route_stops}"
            )

    def get_matrix(self, stops: list[Coordinate], costing: str = None) -> MatrixResult:
        """Get time/distance matrix from Google Maps API from all stops to all stops.

        Args:
            stops (list[Coordinate]): List of stops.
            costing (str): Costing model.
        """
        if costing is None:
            costing = "driving"
        n = len(stops)
        logger.info(f"Getting time and distance matrix for {n} stops")
        time_matrix = np.zeros((n, n))
        distance_matrix = np.zeros((n, n))
        # Divide the matrix into chuncks of maximum max_matrix_elements elements
        slices = slice_matrix(0, n, 0, n, self.max_matrix_elements)
        for slice in slices:
            logger.info(
                f"Calling Matrix API from origin stops {slice['start_row']} "
                f"to {slice['end_row']} and destination stops "
                f"{slice['start_col']} to {slice['end_col']}"
            )
            origin_stops = stops[slice["start_row"] : slice["end_row"]]
            destination_stops = stops[slice["start_col"] : slice["end_col"]]

            origin = self._prepare_stops(origin_stops)
            destination = self._prepare_stops(destination_stops)

            response = self.client.distance_matrix(
                origins=origin, destinations=destination, mode=costing
            )
            m = self._parse_matrix_response(response)
            time_matrix[
                slice["start_row"] : slice["end_row"],
                slice["start_col"] : slice["end_col"],
            ] = m.time_matrix

            distance_matrix[
                slice["start_row"] : slice["end_row"],
                slice["start_col"] : slice["end_col"],
            ] = m.distance_matrix

        return MatrixResult(time_matrix, distance_matrix)

    def _combine_routes(
        self, routes: list[schemas.CartographyRoute]
    ) -> schemas.CartographyRoute:
        """Combine routes into a single route.

        Args:
            routes (list[schemas.CartographyRoute]): List of routes.
        """
        coordinates = []
        times = []
        distances = []
        for route in routes:
            coordinates.extend(route.coordinates)
            times.extend(route.times)
            distances.extend(route.distances)
        return schemas.CartographyRoute(
            coordinates=coordinates, times=times, distances=distances
        )

    def get_route(
        self, stops: list[Coordinate], costing: str = None
    ) -> schemas.CartographyRoute:
        """Get route from Google Maps API.

        Args:
            stops (list[Coordinate]): List of stops.
            costing (str): Costing model.
        """
        if costing is None:
            costing = "driving"
        # Divide the route in chunks of self.max_route_stops stops
        start_idx, end_idx = 0, min(len(stops), self.max_route_stops)
        routes = []
        while (end_idx <= len(stops)) and (start_idx < (len(stops) - 1)):
            logger.info(f"Getting route from stop {start_idx} to {end_idx}")
            # prepare request and call API
            request = self._prepare_request_route(stops[start_idx:end_idx], costing)
            response = self.client.directions(**request)
            routes.append(self._parse_route_response(response))
            # update indexes
            start_idx = end_idx - 1
            end_idx = min(end_idx + self.max_route_stops, len(stops))
        return self._combine_routes(routes)
