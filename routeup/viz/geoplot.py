#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Geoplot class.

.. doctest::

    >>> from pydantic_extra_types.coordinate import Coordinate
    >>> from routeup.viz.geoplot import GeoPlot
    >>> mymap = GeoPlot(bbox=None)
    >>> mymap.plot_point(point=Coordinate(latitude=42.511021, longitude=1.538086), color="red", text="origin", icon="car")
    >>> mymap.plot_point(point=Coordinate(latitude=42.512021, longitude=1.537886), color="lightred", text="stop with id:...", icon="info")
    >>> mymap.plot_point(point=Coordinate(latitude=42.513021, longitude=1.536886), color="blue", text="destination", icon="ok")
    >>> mymap.save("mymap.html")
"""

import folium
import numpy as np
from pydantic_extra_types.coordinate import Coordinate, Latitude, Longitude

_DEFAULT_BBOX = [(24, 0.6), (44, 2.3)]


class GeoPlot:
    """Class to handle folium plots."""

    def __init__(self, bbox: list[tuple[float, float]] | None = None, **kwds):
        """Constructor.

        Args:
            bbox: bounding box of the map
            **kwds: any other parameter for the folium.Map
        """
        bounds = _DEFAULT_BBOX if bbox is None else bbox
        self.gpmap = folium.Map(**kwds)
        self.gpmap.fit_bounds(bounds=bounds)

    def _get_icon(
        self, *, color: str, icon: str, prefix: str | None = None
    ) -> folium.Icon:
        """Get the icon.

        Args:
            color: color of the icon.
            icon: name of the icon.
            prefix: prefix of the icon.

        Returns:
            The folium.Icon object
        """
        if prefix is None:
            return folium.Icon(
                color=color,
                icon=icon,
            )
        else:
            return folium.Icon(color=color, prefix=prefix, icon=icon)

    def plot_point(
        self,
        point: Coordinate,
        *,
        color: str = "blue",
        text: str = "",
        icon: str = "ok",
        prefix: str | None = None,
    ):
        """Add point on the gpmap.

        Args:
            point: the point to be added
            color: color of the point
            text: plain or html text to appear on mouseover
            icon: The name of the marker sign ('ok', 'info', 'car', ...)
            prefix: depends on the assume icons
        """
        folium.Marker(
            location=[point.latitude, point.longitude],
            icon=self._get_icon(color=color, icon=icon, prefix=prefix),
            tooltip=f"{text}",
        ).add_to(self.gpmap)

    def plot_points(
        self, points: list[Coordinate], *, color: str = "blue", icon: str = "ok", **kwds
    ):
        """Add points on the gpmap."""
        raise NotImplementedError

    def plot_polyline(
        self,
        points: list[tuple[float, float]],
        *,
        color: str = "lightred",
        with_arrows: bool = False,
    ):
        """Add polyline on the gpmap."""
        folium.PolyLine(points, color=color, opacity=0.9).add_to(self.gpmap)
        if with_arrows:
            for i, point in enumerate(points[:-1]):
                arrow = self._get_arrows(
                    point1=Coordinate(
                        latitude=Latitude(point[0]), longitude=Longitude(point[1])
                    ),
                    point2=Coordinate(
                        latitude=Latitude(points[i + 1][0]),
                        longitude=Longitude(points[i + 1][1]),
                    ),
                    color=color,
                    size=10,
                )
                arrow.add_to(self.gpmap)
        # Define a custom legend

    def _get_bearing(self, *, point1: Coordinate, point2: Coordinate) -> float:
        """Returns compass bearing from point p1 to p2.

        See Also:
             https://gist.github.com/jeromer/2005586

        Args:
            point1: Point 1 (from)
            point2: Point 2 (towards)

        Returns:
          compass bearing with orientation north
        """
        lon_diff = np.radians(point2.longitude - point1.longitude)
        lat1 = np.radians(point1.latitude)
        lat2 = np.radians(point2.latitude)
        x = np.sin(lon_diff) * np.cos(lat2)
        y = np.cos(lat1) * np.sin(lat2) - np.sin(lat1) * np.cos(lat2) * np.cos(lon_diff)
        bearing = np.degrees(np.arctan2(x, y))

        # adjusting for compass bearing
        if bearing < 0:
            bearing = bearing + 360

        return bearing

    def _get_arrows(
        self,
        *,
        point1: Coordinate,
        point2: Coordinate,
        color: str = "gray",
        size: int = 6,
        n_arrows: int = 1,
    ) -> folium.RegularPolygonMarker:
        """Get a list of arrows to be plotted between the points.

        Args:
            point1: Point 1 (from)
            point2: Point 2 (towards)
            color: the color of the arrow head
            size: The size of the arrow head value is 6
            n_arrows: number of arrows to create. Default value is 3

        Returns:
            A list of folium.RegularPolygonMarker to be plotted
        """
        # Getting the rotation needed for the arrow head.
        # Subtracting 90 degrees to account for the head's orientation
        rotation = int(self._get_bearing(point1=point1, point2=point2)) - 90

        # Get the middle of an evenly spaced list of latitudes and
        # longitudes for the arrows.
        arrow_lats = np.linspace(point1.latitude, point2.latitude, n_arrows + 2)[
            1 : (n_arrows + 1)
        ]
        arrow_lons = np.linspace(point1.longitude, point2.longitude, n_arrows + 2)[
            1 : (n_arrows + 1)
        ]
        arrows = []

        # appending the arrows heads to a list
        for points in zip(arrow_lats, arrow_lons):
            arrows.append(
                folium.RegularPolygonMarker(
                    location=points,
                    fill_color=color,
                    color=color,
                    number_of_sides=3,
                    radius=size,
                    opacity=0.7,
                    rotation=rotation,
                )
            )
        return arrows[0]

    def save(self, filename: str):
        """Save the map to a file.

        Args:
            filename: File name and format to save the map.
        """
        self.gpmap.save(filename)

    def add_polyline_legend(self, elements: list[dict]):
        """Add a legend to the map.

        Args:
            elements: List of dictionaries with the color and name of the polyline.
        """
        legend_html = """
            <div style="position: fixed; bottom: 50px; left: 50px; z-index:9999; font-size: 14px; background-color:white; padding: 20px; border: 2px solid grey; width: 200px;">
            """

        for polyline in elements:
            color = polyline["color"]
            name = polyline["name"]
            legend_html += f"""
                <div style="display: flex; align-items: center; margin-top: 10px;">
                    <svg height="20" width="20">
                        <line x1="0" y1="10" x2="20" y2="10" style="stroke:{color};stroke-width:3" />
                    </svg>
                    <p style="margin-left: 10px;">{name}</p>
                </div>
                """

        legend_html += """
            </div>
            """
        self.gpmap.get_root().html.add_child(folium.Element(legend_html))
