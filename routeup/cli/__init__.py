#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Command line interface**.

Basic CLI to implement routeup functionalities.

Using `Typer <https://typer.tiangolo.com/>`_ we build a basic
*Command Line Interface* to interact with
``routeup``'s main functionalities.

Different sub-commands should be coded in separate files, each
of them with its own app Typer instance, and added to the
main app Typer instance defined here via the ``app.add_typer`` method.

There is a special :func:`.main_callback` which enables the
``--version`` option when there is no subcommand:

.. code-block:: console

   $ routeup --version

"""

import sys

import rich
import typer
from rich.panel import Panel
from typer import Option, Typer

from routeup import __version__

app = Typer()
"""Main Typer application.

This is the CLI entrypoint. It has one single ``--version`` option,
defined in :func:`.main_callback`, that can be used to display the
current installed version of ``routeup``.

"""


@app.callback(invoke_without_command=True, no_args_is_help=True)
def main_callback(version: bool = Option(False, help="Show the package version.")):
    """Routeup command line interface."""
    _v = __version__
    _p = sys.platform.capitalize()
    if version is not None:
        rich.print(
            Panel.fit(
                f"RouteUp, version {_v} on {_p}",
                style="bold green",
                title_align="center",
            )
        )


typer_click_object = typer.main.get_command(app)
"""Main Click object derived from the main Typer app."""

if __name__ == "__main__":
    app()
