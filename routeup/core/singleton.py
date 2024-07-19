#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""**Singleton metaclass**.

Singleton metaclass is a template that allows to designe
new classes and create only a single instance of each class
created.

.. doctest::

    >>> from routeup.core import Singleton
    >>> class MyBaseClass(metaclass=Singleton):
    ...     def __init__(self):
    ...         pass

    >>> class MyClass(MyBaseClass, metaclass=Singleton):
    ...     def __init__(self):
    ...         super().__init__()

    >>> m1 = MyClass()
    >>> m2 = MyClass()
    >>> m1 is m2
    True

"""
from threading import Lock
from typing import Dict


class Singleton(type):
    """Thread-safe implementation of Singleton metaclass design pattern."""

    _instances: Dict = {}
    _lock: Lock = Lock()

    def __new__(cls, child_class, child_parents, child_objects):
        """Class constructor adding method to clear cached instances."""

        def clear_cache(cls):
            """Method to clear cached instances."""
            return cls.__class__._instances.pop(cls, None)

        child_objects["clear_cache"] = classmethod(clear_cache)
        return super().__new__(cls, child_class, child_parents, child_objects)

    def __call__(cls, *args, **kwargs):
        """Instance constructor which caches new created instances."""
        with cls._lock:
            if cls not in cls._instances:
                instance = super().__call__(*args, **kwargs)
                cls._instances[cls] = instance
        return cls._instances[cls]
