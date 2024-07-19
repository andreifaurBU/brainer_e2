#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Client utilities.

.. doctest::

    >>> from routeup.data.client.utils import MatrixResult
    >>> from routeup.data.client.utils import slice_matrix
    >>> import numpy as np

    >>> time_matrix = np.array([[1, 2, 3], [4, 5, 6], [7, 8, 9]])
    >>> distance_matrix = np.array([[10, 20, 30], [40, 50, 60], [70, 80, 90]])

    >>> matrix = MatrixResult(time_matrix=time_matrix, distance_matrix=distance_matrix)
    >>> slice = slice_matrix(0, 3, 0, 3, 8)
"""
from dataclasses import dataclass

import numpy as np


@dataclass
class MatrixResult:
    """A dataclass to represent time and distance matrices."""

    time_matrix: np.ndarray
    distance_matrix: np.ndarray


def slice_matrix(start_row, end_row, start_col, end_col, max_elements):
    """Slice a matrix into smaller matrices.

    Args:
        start_row: Initial row index.
        end_row: Final row index.
        start_col: Initial column index.
        end_col: Final column index.
        max_elements: Maximum number of elements in the matrix.
    """
    dimension_1, dimension_2 = end_row - start_row, end_col - start_col
    if (dimension_1 * dimension_2) <= max_elements:
        return [
            {
                "start_row": start_row,
                "end_row": end_row,
                "start_col": start_col,
                "end_col": end_col,
            }
        ]
    else:
        max_length = max(dimension_1, dimension_2)
        new_length = max_length // 2
        if dimension_1 == max_length:
            top = slice_matrix(
                start_row, start_row + new_length, start_col, end_col, max_elements
            )
            down = slice_matrix(
                start_row + new_length, end_row, start_col, end_col, max_elements
            )
            return top + down
        else:
            left = slice_matrix(
                start_row, end_row, start_col, start_col + new_length, max_elements
            )
            right = slice_matrix(
                start_row, end_row, start_col + new_length, end_col, max_elements
            )
            return left + right
