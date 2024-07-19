#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Top level variables**."""

from importlib.metadata import PackageNotFoundError, version

try:
    __version__ = version(__name__)
    """Package version.

    This string contains the package version and can be imported as:

    .. doctest::

        >>> from routeup import __version__

    """
except PackageNotFoundError:
    pass

__all__ = ["__version__"]
