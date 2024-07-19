#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**SQLAlchemy ORM mixins**.

Here we define base mixins for the project's ORMs.
Mixins are used to reduce boilerplate code when defining ORMs.

One can define here fields that are common to many tables.
When defining a new ORM, one can inherit from a Mixin in addition
to the Base ORM class.

To declare a new mixin, simply create inheriting from :class:`object`
and decorate it with :func:`~sqlalchemy.orm.declarative_mixin`.

For instance, if we have a bunch of tables sharing a column named
``name``, we could define the following mixin:

.. doctest::

    >>> from sqlalchemy.orm import Mapped, declarative_mixin, mapped_column
    ...
    >>> @declarative_mixin
    ... class NamedTableMixin:
    ...
    ...     name: Mapped[str] = mapped_column()

And then use it as a base to a database ORM:

.. doctest::

    >>> from sqlalchemy.orm import Mapped, mapped_column
    >>> from routeup.models.base import BaseModel
    ...
    >>> class MyTable(BaseModel, NamedTableMixin):
    ...
    ...     table_pk: Mapped[int] = mapped_column(primary_key=True)
    ...     special_column: Mapped[str] = mapped_column()
    ...
    >>> my_row = MyTable(name="Row name", special_column="Foo")

.. testcleanup::

    BaseModel.metadata.clear()
    BaseModel.registry.dispose()

"""
