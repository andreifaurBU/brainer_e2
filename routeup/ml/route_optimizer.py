#
# Copyright (c) 2024 by Dribia Data Research.
# This file is part of project RouteUp,
# and is released under the MIT License Agreement.
# See the LICENSE file for more information.
#
"""Route Optimizer Class."""
import numpy as np
from ortools.constraint_solver import pywrapcp, routing_enums_pb2

from routeup.core import config, logger
from routeup.core.config.enums import RouteMode
from routeup.schemas.route_optimizer import (
    Fleet,
    GridSearchOutput,
    Route,
    RouteOptimizerInput,
    RouteOptimizerOutput,
    RouteStop,
)


class RouteOptimizer:
    """Route Optimizer Class."""

    def __init__(
        self,
        *,
        route_optimizer_input: RouteOptimizerInput,
        vehicle_penalty: int = 10000,
    ) -> None:
        """Initialize the optimizer.

        Unpacks the input and sets the manager and routing.

        Args:
            route_optimizer_input: Input for the route optimizer.
            vehicle_penalty: Penalty for each vehicle. Defaults to 10000.
        """
        self.mode = route_optimizer_input.mode
        self.time_matrix = np.array(route_optimizer_input.time_matrix)
        self.fleet = route_optimizer_input.fleet
        self.demands = route_optimizer_input.demands
        self.max_travel_time = route_optimizer_input.max_travel_time
        self.slack_time = route_optimizer_input.slack_time
        self.vehicle_penalty = vehicle_penalty
        self.data = self._create_data_model()
        self.manager = self._set_manager()
        self.routing = self._set_routing(
            vehicle_penalty=self.vehicle_penalty, slack_time=self.slack_time
        )

    def _create_data_model(
        self, *, extra_vehicles: int = 0, extra_max_travel_time: int = 0
    ) -> dict:
        """Reprocesses the input data into the format required by ORTools.

        This method takes the input data, including time matrix,
        demands, fleet information, and other parameters, and formats
        it into a dictionary representing the data model suitable for
        use in ORTools.

        Args:
            extra_vehicles (int, optional): Extra vehicles to be added. Defaults to 0.
            extra_max_travel_time (int, optional): Extra maximum travel time. Defaults to 0.

        Returns:
            dict: A dictionary containing the preprocessed data model with the following keys:
                - 'time_matrix': Time matrix for distances between locations. Should be in UNITS
                - 'demands': Passangers to board the bus at each location.
                - 'vehicle_capacities': List of vehicle capacities based on the fleet.
                - 'num_vehicles': Total number of vehicles in the fleet.
                - 'depot': Index representing the depot location.
                - 'max_travel_time': Maximum travel time allowed for vehicles.
        """
        time_matrix = self.time_matrix
        # https://developers.google.com/optimization/routing/routing_tasks#allowing_arbitrary_start_and_end_locations:~:text=To%20set%20up%20the%20problem%20this%20way%2C%20simply%20modify%20the%20distance%20matrix%20so%20that%20distance%20from%20the%20depot%20to%20any%20other%20location%20is%200%2C%20by%20setting%20the%20first%20row%20and%20column%20of%20the%20matrix%20to%20have%20all%20zeros.%20This%20turns%20the%20depot%20into%20a%20dummy%20location%20that%20has%20no%20effect%20on%20the%20optimal%20routes.
        if self.mode == RouteMode.INBOUND:
            # inbound problem: the distance from the depot to any other location is 0
            # set first row to -slack_time for starting from any point. Since going from the depot to anywhere  should
            # take 0, we have to substract the slack_time to the time_matrix
            time_matrix[0, :] = -self.slack_time
        else:
            # outbound problem: the distance from any location to the depot is 0
            # set first column to -slack time for ending at any point. Since going from anywhere to the depot should
            # take 0, we have to substract the slack_time to the time_matrix
            time_matrix[:, 0] = -self.slack_time

        vehicle_capacities = [
            item.capacity for item in self.fleet for _ in range(item.number_of_vehicles)
        ]

        # Add extra vehicles of max_capacity

        max_capacity = max(vehicle_capacities)  # Get the maximum capacity
        vehicle_capacities.extend([max_capacity] * extra_vehicles)

        data = {
            "time_matrix": self.time_matrix,
            "demands": self.demands,
            "vehicle_capacities": vehicle_capacities,
            "num_vehicles": len(vehicle_capacities),
            "depot": 0,
            "max_travel_time": self.max_travel_time + extra_max_travel_time,
        }

        return data

    def _set_manager(self) -> pywrapcp.RoutingIndexManager:
        """Sets up the manager for ORTools.

        This method creates and configures the manager, which is used to compute distances
        between stops in the routing problem.

        Returns:
            pywrapcp.RoutingIndexManager: Configured routing index manager with the following parameters:
                - Number of nodes in the problem (length of the time matrix).
                - Number of vehicles in the fleet.
                - Index representing the depot location.
        """
        data = self.data

        # The distance between two nodes is the time to travel between the two nodes.
        return pywrapcp.RoutingIndexManager(
            len(data["time_matrix"]), data["num_vehicles"], data["depot"]
        )

    def _set_routing(
        self, *, vehicle_penalty: int = 10000, slack_time: int = 120
    ) -> pywrapcp.RoutingModel:
        """Sets up the routing, a class of the ORTools with the system info.

        This method creates and configures the routing model, imposes constraints,
        and defines the system based on the provided data.

        Args:
            vehicle_penalty: Penalty cost for each vehicle used in the routing. Defaults to 10000. If set to 0, it will priroritize to end the route as fast as possible with the available fleet.
            slack_time: Time spent in each stop. Defaults to 120.

        Returns:
            pywrapcp.RoutingModel: Configured routing model with constraints and defined system.
        """
        data = self.data
        # Create Routing Model.
        routing = pywrapcp.RoutingModel(self.manager)

        # Create and register a transit callback.
        # The callback is used to compute the cost of traveling between two nodes.
        def time_callback(from_index, to_index):
            """Returns the distance between the two nodes."""
            # Convert from routing variable Index to distance matrix NodeIndex.
            from_node = self.manager.IndexToNode(from_index)
            to_node = self.manager.IndexToNode(to_index)
            return data["time_matrix"][from_node][to_node] + slack_time

        transit_callback_index = routing.RegisterTransitCallback(time_callback)

        # We add a penalty for each vehicle used.
        for vehicles in range(data["num_vehicles"]):
            routing.SetFixedCostOfVehicle(vehicle_penalty, vehicles)

        # Define cost of each arc. Each unit of time adds a unit of penalty
        routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

        # Add demand callback. It counts the amount of passangers each vehicle is carrying at any point of the route.
        def demand_callback(from_index):
            """Returns the demand of the node."""
            from_node = self.manager.IndexToNode(from_index)
            return data["demands"][from_node]

        demand_callback_index = routing.RegisterUnaryTransitCallback(demand_callback)

        # Add Capacity constraint. Vehicles cannot exceed their capacity.
        routing.AddDimensionWithVehicleCapacity(
            demand_callback_index,
            0,  # null capacity slack
            data["vehicle_capacities"],  # vehicle maximum capacities
            True,  # start cumul to zero
            "Capacity",
        )

        # Add Time constraint. Vehicles can not travel more than a certain amount of time.
        routing.AddDimension(
            transit_callback_index,
            0,  # no slack
            data["max_travel_time"],  # vehicle maximum travel time
            True,  # start cumul to zero
            "Time",
        )

        return routing

    def optimizer_solver(
        self,
        *,
        local_search: bool = False,
        max_time: int = 3600,
    ) -> RouteOptimizerOutput:
        """Solve the routing problem using ORTools.

        This method configures and solves the routing problem using
        ORTools. It allows specifying whether to enable local search
        and sets a maximum time limit for the optimization.


        Args:
            local_search: Enable Guided Local Search metaheuristic if True. Defaults to False.
            max_time: Maximum time limit for optimization in seconds. Defaults to 3600. Units in seconds

        Returns:
            RouteOptimizerOutput: An object containing the optimization results.
        """
        # Setting first solution heuristic.
        search_parameters = pywrapcp.DefaultRoutingSearchParameters()
        search_parameters.first_solution_strategy = (
            routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
        )

        # If local search is enabled, we use the Guided Local Search metaheuristic.
        if local_search:
            search_parameters.local_search_metaheuristic = (
                routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
            )
            search_parameters.time_limit.seconds = max_time
        # Solve the problem.
        solution = self.routing.SolveWithParameters(search_parameters)

        # Print solution on console.
        if solution:
            logger.info("Solution found")
            return self._extract_output(solution)
        else:
            logger.error("No valid solution found")
            return RouteOptimizerOutput(max_travel_time=0, routes=[], slack_time=0)

    def grid_search(self) -> GridSearchOutput:
        """Solve the routing problem using ORTools.

        This method configures and solves the routing problem using ORTools.
        It allows specifying whether to enable local search and sets a
        maximum time limit for the optimization.

        Returns:
            RouteOptimizerOutput: An object containing the optimization results.
        """
        logger.info("Starting the grid search process.")
        logger.debug("Trying to solve the problem without grid search")
        # Setting first solution heuristic.
        search_parameters = pywrapcp.DefaultRoutingSearchParameters()
        search_parameters.first_solution_strategy = (
            routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
        )
        # Solve the problem.
        search_parameters.time_limit.seconds = config.APP_CONFIG.grid_search_time_limit
        solution = self.routing.SolveWithParameters(search_parameters)

        # Print solution on console.
        if solution:
            logger.info("Solution found")
            return GridSearchOutput(
                extra_time=0,
                extra_vehicles=Fleet(number_of_vehicles=0, capacity=0),
                message="Solution found without grid search",
            )
        else:
            logger.debug("No valid solution found. Trying grid search")
            grid_search_output = self._grid_search_solver()
            logger.info(grid_search_output.message)
            return grid_search_output

    def _extract_output(self, solution) -> RouteOptimizerOutput:
        """Extracts output from the solution object provided by ORTools.

        Args:
            solution: The solution object obtained from ORTools.

        Returns:
            RouteOptimizerOutput: An object containing the extracted optimization results, including:
                - 'max_travel_time': Maximum travel time among all routes.
                - 'routes': List of routes, each represented as a Route object.
                  Each Route object includes information about the vehicle's capacity, travel occupancy,
                  travel time, and a list of RouteStop objects representing each stop on the route.
        """
        data = self.data
        manager = self.manager
        routing = self.routing
        routes = []

        for vehicle_id in range(data["num_vehicles"]):
            index = routing.Start(vehicle_id)
            route_stops = []
            route_time = 0
            route_load = 0

            while not routing.IsEnd(index):
                node_index = manager.IndexToNode(index)

                cumul_var = routing.GetDimensionOrDie("Time").CumulVar(index)
                arrival_time = solution.Min(cumul_var)

                route_load += data["demands"][node_index]
                previous_index = index
                index = solution.Value(routing.NextVar(index))
                route_time += routing.GetArcCostForVehicle(
                    previous_index, index, vehicle_id
                )
                route_stop = RouteStop(
                    stop_id=node_index,
                    stop_demand=data["demands"][node_index],
                    travel_time=arrival_time,
                )
                route_stops.append(route_stop)

            # If the vehicle has traveled, we added the vehicle penalty to the time, so we have to substract it to get
            # the real travel time
            if route_time > 0:
                route_time -= self.vehicle_penalty
            if self.mode == RouteMode.INBOUND:
                depot_stop = RouteStop(
                    stop_id=0,
                    stop_demand=0,
                    vehicle_occupancy=route_load,
                    travel_time=route_time,
                )
                route_stops = route_stops[1:] + [depot_stop]

            route = Route(
                vehicle_capacity=data["vehicle_capacities"][vehicle_id],
                vehicle_travel_occupancy=route_load,
                vehicle_travel_time=route_time,
                route_stops=route_stops,
            )
            routes.append(route)

        output = RouteOptimizerOutput(
            max_travel_time=data["max_travel_time"],
            routes=routes,
            slack_time=self.slack_time,
        )
        return output

    def _grid_search_solver(
        self, iter_max_extra_time: int = 3, iter_max_extra_vehicle: int = 3
    ) -> GridSearchOutput:
        """Solve the routing problem using ORTools.

        This method configures and solves the routing problem using ORTools. It allows specifying
        whether to enable local search and sets a maximum time limit for the optimization.

        Args:
            iter_max_extra_time: Maximum number of iterations to try with extra time. Defaults to 3.
            iter_max_extra_vehicle: Maximum number of iterations to try with extra vehicles. Defaults to 3.

        Returns:
            RouteOptimizerOutput: An object containing the optimization results.
        """
        message = []
        extra_time = 0
        extra_vehicles = Fleet(number_of_vehicles=0, capacity=0)
        for i in range(1, iter_max_extra_time + 1):
            logger.debug(
                f"Trying to solve the problem with {i} minutes more of extra time"
            )
            self.data = self._create_data_model(
                extra_vehicles=0, extra_max_travel_time=i * 60
            )
            self.manager = self._set_manager()
            self.routing = self._set_routing(vehicle_penalty=self.vehicle_penalty)

            # Setting first solution heuristic.
            search_parameters = pywrapcp.DefaultRoutingSearchParameters()
            search_parameters.first_solution_strategy = (
                routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
            )
            search_parameters.time_limit.seconds = (
                config.APP_CONFIG.grid_search_time_limit
            )
            # Solve the problem.
            solution = self.routing.SolveWithParameters(search_parameters)

            # Print solution on console.
            if solution:
                extra_time = i * 60
                message.append(
                    f"Solution can be found with {i} minutes more of extra time"
                )
                break

        for i in range(1, iter_max_extra_vehicle + 1):
            logger.debug(f"Trying to solve the problem with {i} vehicle/s more")
            self.data = self._create_data_model(
                extra_vehicles=i, extra_max_travel_time=0
            )
            self.manager = self._set_manager()
            self.routing = self._set_routing(vehicle_penalty=self.vehicle_penalty)

            # Setting first solution heuristic.
            search_parameters = pywrapcp.DefaultRoutingSearchParameters()
            search_parameters.first_solution_strategy = (
                routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
            )
            search_parameters.time_limit.seconds = (
                config.APP_CONFIG.grid_search_time_limit
            )
            # Solve the problem.
            solution = self.routing.SolveWithParameters(search_parameters)

            # Print solution on console.
            if solution:
                vehicle_capacities = [
                    item.capacity
                    for item in self.fleet
                    for _ in range(item.number_of_vehicles)
                ]
                extra_vehicles = Fleet(
                    number_of_vehicles=i, capacity=max(vehicle_capacities)
                )
                message.append(f"Solution can be found with {i} vehicle/s more \n")
                break

        if len(message) == 0:
            message.append("No solution found with extra vehicles or extra time.")
        self.data = self._create_data_model(extra_vehicles=0, extra_max_travel_time=0)
        self.manager = self._set_manager()
        self.routing = self._set_routing(vehicle_penalty=self.vehicle_penalty)

        return GridSearchOutput(
            extra_time=extra_time,
            extra_vehicles=extra_vehicles,
            message=" or ".join(message),
        )
