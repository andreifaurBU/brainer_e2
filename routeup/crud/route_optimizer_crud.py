#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Response CRUD."""
from routeup.core import config
from routeup.crud.bq_crud_base import BigQueryCRUDBase
from routeup.schemas_df.route_optimizer import RouteOptimizerDFSchema


# %% ======= CRUD for Response =======
class RouteOptimizerCRUD(BigQueryCRUDBase[RouteOptimizerDFSchema]):
    """CRUD for train features_service_is_play_pressed."""

    pass


crud_route_optimizer = RouteOptimizerCRUD(
    project_id=config.GCLOUD_PROJECT_ID,
    dataset_id=config.GCLOUD_DATASET_ANALYTICS,
    table_id="route_optimizer",
    schema_df=RouteOptimizerDFSchema,
    schema_path=config.PATH_JSON_SCHEMA / "route_optimizer.json",
    primary_key="id",
)
