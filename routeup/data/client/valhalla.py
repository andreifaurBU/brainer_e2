#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Valhalla client."""
import numpy as np
import polyline
import requests
from pydantic_extra_types.coordinate import Coordinate

from routeup import schemas
from routeup.core import config, logger
from routeup.core.config import DistanceMetric
from routeup.core.exceptions import InvalidResponseFormat
from routeup.data.client.utils import MatrixResult
from routeup.schemas.cartography import RequestStopValhalla


class ValhallaClient:
    """Valhalla client."""

    def __init__(
        self,
        base_url: str = "http://localhost:8002/",
    ):
        """Valhalla client.

        Args:
            base_url (str): Valhalla API URL.

        """
        self.__base_url = base_url
        self.__headers = {"Content-Type": "application/json"}
        self._polyline_precision = 6  # six degrees of precision in valhalla
        if config.DISTANCE_METRIC == DistanceMetric.METERS:
            self.distance_conversion_factor = 1000
        elif config.DISTANCE_METRIC == DistanceMetric.KILOMETERS:
            self.distance_conversion_factor = 1
        else:
            raise ValueError("Invalid distance metric")

    def __call_api_request(self, data: dict, service: str) -> dict | None:
        """Call Valhalla API and return JSON data.

        Args:
            data (dict): API data.
            service (str): API service.
        """
        try:
            # Send API request
            url = self.__base_url + service
            response = requests.get(url, headers=self.__headers, json=data)

            # Check status code
            if response.status_code == 200:
                # Success! Do something with the data
                logger.info(f"Successfully retrieved data from {service} API.")
                return response.json()
            else:
                # Error! Log details and raise an exception
                logger.error(
                    f"API request failed with status code: {response.status_code}"
                )
                logger.error(f"Error message: {response.text}")
                raise requests.exceptions.RequestException(
                    "Valhalla API request failed. Check stop coordinates and costing mode."
                )

        except requests.exceptions.RequestException as e:
            # Handle other request errors
            logger.error(f"{e}")
            raise e

    def _parse_matrix_response(self, response: dict) -> MatrixResult:
        """Parses a matrix API response from Valhalla and returns distance and time matrices.

        Args:
            response (dict): The matrix API response in JSON format.
        """
        # Check expected keys and data types
        if not isinstance(response, dict):
            raise InvalidResponseFormat("Response is not a dictionary.")
        if "sources" not in response:
            raise InvalidResponseFormat("sources' keys not found.")
        if "targets" not in response:
            raise InvalidResponseFormat("'targets' keys not found.")
        if ("sources_to_targets" not in response) or (
            not isinstance(response["sources_to_targets"], list)
        ):
            raise InvalidResponseFormat(
                "'sources_to_targets' key not found or not a list."
            )

        # Create empty distance and time matrices
        num_sources = len(response["sources"])
        num_targets = len(response["targets"])
        distance_matrix = np.zeros((num_sources, num_targets))
        time_matrix = np.zeros((num_sources, num_targets))

        # Fill distance and time matrices from response data
        try:
            for i, source_to_targets in enumerate(response["sources_to_targets"]):
                for j, target_data in enumerate(source_to_targets):
                    if (target_data["distance"] is None) or (
                        target_data["time"] is None
                    ):
                        logger.warning(
                            f"Missing matrix API data for source {i} and target {j}."
                        )
                        logger.debug("Calculating route instead.")
                        start = Coordinate(
                            latitude=response["sources"][i]["lat"],
                            longitude=response["sources"][i]["lon"],
                        )
                        end = Coordinate(
                            latitude=response["targets"][j]["lat"],
                            longitude=response["targets"][j]["lon"],
                        )
                        route = self.get_route([start, end])
                        distance_matrix[i, j] = route.distances[0]
                        time_matrix[i, j] = route.times[0]
                    else:
                        distance_matrix[i, j] = (
                            target_data["distance"] * self.distance_conversion_factor
                        )
                        time_matrix[i, j] = target_data["time"]
        except KeyError as e:
            raise InvalidResponseFormat(f"{e}") from e

        return MatrixResult(time_matrix, distance_matrix)

    def _parse_route_response(self, response: dict) -> schemas.CartographyRoute:
        """Parses a route API response from Valhalla and returns the route info.

        Args:
            response (dict): The route API response in JSON format.
        """
        # Check expected keys and data types
        if not isinstance(response, dict):
            raise InvalidResponseFormat("Response is not a dictionary.")
        if "trip" not in response:
            raise InvalidResponseFormat("'trip' key not found.")
        if "legs" not in response["trip"]:
            raise InvalidResponseFormat("'legs' key not found.")
        if not isinstance(response["trip"]["legs"], list):
            raise InvalidResponseFormat("'legs' key is not a list.")

        coordinates, times, distances = [], [], []
        n_legs = len(response["trip"]["legs"])
        try:
            for i in range(n_legs):
                encoded_polyline = response["trip"]["legs"][i]["shape"]
                coordinates += polyline.decode(
                    encoded_polyline, precision=self._polyline_precision
                )
                times.append(response["trip"]["legs"][i]["summary"]["time"])
                distances.append(
                    response["trip"]["legs"][i]["summary"]["length"]
                    * self.distance_conversion_factor
                )
        except KeyError as e:
            raise InvalidResponseFormat(f"{e}") from e

        return schemas.CartographyRoute(
            coordinates=coordinates, times=times, distances=distances
        )

    @staticmethod
    def _prepare_stops(stops: list[Coordinate]) -> list[dict]:
        """Prepare stops for API request.

        Args:
            stops (list[Coordinate]): List of stops.
        """
        return [
            RequestStopValhalla(lat=stop.latitude, lon=stop.longitude).model_dump()
            for stop in stops
        ]

    def get_matrix(self, stops: list[Coordinate], costing: str = None) -> MatrixResult:
        """Get time and distance matrix from Valhalla API from all stops to all stops.

        Args:
            stops (list[Coordinate]): List of stops.
            costing (str): Costing model.
        """
        if costing is None:
            costing = "bus"
        # Prepare API data
        sources = self._prepare_stops(stops)
        data = {"sources": sources, "targets": sources, "costing": costing}
        # Call API
        response = self.__call_api_request(data, "sources_to_targets")
        # Parse response
        return self._parse_matrix_response(response)

    def get_route(
        self, stops: list[Coordinate], costing: str = None
    ) -> schemas.CartographyRoute:
        """Get route from Valhalla API.

        Args:
            stops (list[Coordinate]): List of stops.
            costing (str): Costing model.
        """
        if costing is None:
            costing = "bus"
        # Prepare API data
        data = {
            "locations": self._prepare_stops(stops),
            "costing": costing,
        }

        # Call API
        response = self.__call_api_request(data, "route")
        # Parse response
        return self._parse_route_response(response)
