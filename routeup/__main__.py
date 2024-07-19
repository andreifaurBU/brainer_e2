#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**CLI access point**.

With this module we enable the ``python -m routeup``
functionality.

The CLI should also be accessible through the command:
``routeup``.

"""

from routeup.cli import app

if __name__ == "__main__":
    app()
