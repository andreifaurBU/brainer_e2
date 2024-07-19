#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Core utility functions**.

Contains generic utility functions, e.g. those
that depend only on the Python standard library.

"""

import re


def camel_to_snake_case(s):
    """Convert strings from ``CamelCase`` to ``lower_underscore``.

    .. doctest::

        >>> from routeup.core import utils
        >>> utils.camel_to_snake_case("MyTable")
        'my_table'

    Args:
        s: String in ``CamelCase`` format.

    Returns: String in ``lower_underscore`` format.

    """
    matches = re.finditer(".+?(?:(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])|$)", s)
    return "_".join([m.group(0).lower() for m in matches])
