#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Region CRUD."""
from routeup.core import config, logger
from routeup.core.exceptions import NoRegionFoundError
from routeup.crud.bq_crud_base import BigQueryCRUDBase
from routeup.schemas_df.region import RegionDFSchema


# %% ======= CRUD for Region =======
class RegionCRUD(BigQueryCRUDBase[RegionDFSchema]):
    """CRUD for train features_service_is_play_pressed."""

    def get_region_containing_point(self, lat: float, lng: float) -> str:
        """Get name of region containing the given latitude and longitude point.

        Args:
            lat: Latitude of the point.
            lng: Longitude of the point.
        """
        # Construct the query to fetch regions containing the given point
        query = f""" SELECT r.name
                     FROM `{self.project_id}.{self.dataset_id}.{self.table_id}` r
                     WHERE ST_CONTAINS(ST_GEOGFROMTEXT(r.geometry), ST_GEOGPOINT({lng},{lat}))"""

        # Run the query
        df = self.client.query(query).to_dataframe()

        if not df.empty:
            region_name = df.iloc[0]["name"]
            logger.info(f"Region found for point ({lat},{lng}): {region_name}")
            if len(df) > 1:
                logger.warning(
                    f"Multiple regions found for point ({lat},{lng}). Returning the first one."
                )
            return region_name
        else:
            logger.error(f"No region found for point ({lat},{lng}).")
            raise NoRegionFoundError(
                f"No Vallhalla region found for point ({lat},{lng})."
            )


crud_region = RegionCRUD(
    project_id=config.GCLOUD_PROJECT_ID,
    dataset_id=config.GCLOUD_DATASET_ANALYTICS,
    table_id="region",
    schema_df=RegionDFSchema,
    schema_path=config.PATH_JSON_SCHEMA / "region.json",
    primary_key="name",
)
