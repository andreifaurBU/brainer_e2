<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\RouteUpExecutionsRepository;
use Core\Router\Domain\Repositories\StopRepository;
use Core\Router\Infrastructure\Maps\Library\PolylineConversor;
use Core\Router\Domain\Repositories\RouteUpTaskInfoRepository;
use Core\Router\Domain\Repositories\RouteUpFileRepository;

class GetRouteUpExecutionById
{

    public function __construct(
        private RouteUpExecutionsRepository $routeUpExecutionsRepository,
        private StopRepository $stopRepository,
        private PolylineConversor $polylineConversor,
        private RouteUpTaskInfoRepository $routeUpTaskInfoRepository,
        private RouteUpFileRepository $routeUpFileRepository
    ){}

    public function __invoke(string $task_id) {
        $row = $this->routeUpExecutionsRepository->getExecutionByTaskId($task_id);
        if (count($row) === 0){
            throw new \Exception("This execution task id does not exist");
        }
        return $this->formatAllData($row[0]);
    }

    private function formatAllData($data){
        $taskInfo = $this->routeUpTaskInfoRepository->getTaskInfoById($data['task_id']);
        $data_input = json_decode($data['input'], true);
        $data_output = json_decode($data['output'], true);
        $fileInfo = $this->routeUpFileRepository->getRouteUpFile($taskInfo['file_id']);
        return [
            'status' => $data['status'],
            'type_of_route' => $data_input['mode'] === 'inbound' ? 'going' : 'return',
            'arrival_departure_time' => substr($data_input['depot_time'], 11, 5),
            'filename' => $fileInfo['file_name'],
            'max_route_time' => $data_input['max_travel_time'] / 60,
            'user' => $taskInfo['user'],
            'routes' => isset($data_output) ? $this->formatRoutes($data_output['routes']) : [],
            'fleet_configuration' => $this->formatFleet($data_input['fleet']),
            'advanced_configuration' => $this->formatAdvancedConfiguration($data_input)
        ];
    }

    private function formatRoutes($routes){
        $newData = [];
        $stops_ids = [];
        foreach ($routes as $i => $route) {
            $stops = $this->formatStops($route['stops']);
            $stops_ids = array_merge($stops['ids'], $stops_ids);
            $newData[] = [
                'name' => "Route " . ($i+1),
                'passengers' => $route['occupancy'] . "/" . $route['vehicle_capacity'],
                'polyline_raw' => $this->polylineConversor->coordinateToPolyline($route['polyline']),
                'stops' => $stops['data'],
            ];
        }

        //ADD STOP INFO
        $stops_info = $this->stopRepository->getStopsByIds($stops_ids);

        foreach ($newData as &$route) {
            foreach ($route['stops'] as &$stop) {
                foreach ($stops_info as $stop_info) {
                    if ($stop['id'] === $stop_info['id']) {
                        $stop['name'] = $stop_info['name'];
                        $stop['address'] = $stop_info['address'];
                        $stop['type'] = null; //TODO: COMPLETAR
                    }
                }
            }
        }

        return $newData;
    }

    private function formatStops($stops){
        $newData = [];
        $ids = [];
        foreach ($stops as $stop) {
            $ids[] = $stop['id'];
            $newData[] = [
                'id' => $stop['id'],
                'name' => null,
                'address' => null,
                'time' => $stop['stop_time'],
                'pax' => $stop['n_passengers'],
                'type' => null, // 1 PICKUP 2 ES PICKUP/DROPOFF 3 DROPOFF
                'lat' => $stop['coordinate']['latitude'],
                'lng' => $stop['coordinate']['longitude'],
            ];
        }

        return ['data' => $newData, 'ids' => $ids];
    }

    private function formatFleet($data){
        $newData = [];
        foreach ($data as $item) {
            $newData[] = [
                'number_of_vehicles' => $item['number_of_vehicles'],
                'capacity' => $item['capacity']
            ];
        }
        return $newData;
    }

    private function formatAdvancedConfiguration($data){
        return [
            'cartography_system' => $data['cartography_provider'],
            'slack_time' => $data['slack_time'] / 60,
            'local_search' => $data['local_search'],
            'max_time_search' => $data['max_time_search'] / 60,
            'grid_search' => false, //TODO: NO EXISTE EN EL INPUT
            'vehicle_penalty' => $data['vehicle_penalty'],
        ];
    }

}
