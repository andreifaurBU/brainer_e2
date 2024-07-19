#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Base classes for Pydantic data schemas**.

Here we define the base schemas that feed schemas created within
this package.

Base schemas are meant to reduce boilerplate code by means of
inheritance. Therefore, if one has a bunch of schemas sharing,
let's say, the ``name`` attribute, one can define a base schema:

.. doctest::

    >>> from routeup.schemas.base import BaseSchema
    ...
    >>> class NamedBase(BaseSchema):
    ...
    ...     name: str

And then use it in other schema modules:

    >>> class Person(NamedBase):
    ...
    ...     pass
    ...
    >>> Person(name="John")
    Person(name='John')

"""

from pydantic import BaseModel
from pydantic_settings import SettingsConfigDict


class BaseSchema(BaseModel):
    """General base class for schemas.

    Every schema within the schemas package will somehow
    inherit this class.

    By means of this class we ensure that

    * ``from_attributes=True`` so that schemas are fully compatible with
      SQLAlchemy ORMs defined in :mod:`.models`.

    * ``frozen=True`` so that all our schemas are hashable by default.
      If a schema has non-hashable fields, you need to override this
      configuration to set ``frozen = False``.

    """

    """Custom schema configuration class."""
    model_config = SettingsConfigDict(
        frozen=True,  # Make subclasses hashable.
        from_attributes=True,  # Enable SQLAlchemy compatibility.
    )


__all__ = ["BaseSchema"]
