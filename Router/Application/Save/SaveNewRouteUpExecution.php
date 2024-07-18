<?php

namespace Core\Router\Application\Save;

use Core\Router\Domain\Repositories\RouteUpNewExecution;
use Core\Router\Domain\Repositories\RouteUpTaskInfoRepository;
use Core\Router\Domain\Repositories\CSVReaderRepository;
use Core\Router\Domain\Repositories\RouteUpExecutionsRepository;

class SaveNewRouteUpExecution
{

    public function __construct(
        private RouteUpNewExecution $routeUpNewExecution,
        private CSVReaderRepository $csvReaderRepository,
        private RouteUpTaskInfoRepository $routeUpTaskInfoRepository,
        private RouteUpExecutionsRepository $routeUpExecutionsRepository
    ){}

    public function __invoke($file, $additional_data, $file_id, $user) {
        if (get_class($file) === "Core\Router\Infrastructure\Persistence\Model\RouteUpFile") {
            $this->csvReaderRepository->loadFromContent($file['file_content']);
        } else {
            $this->csvReaderRepository->load($file);
        }

        if (
            isset($additional_data['route_type'])
            && (
                $additional_data['route_type'] === "going"
                || $additional_data['route_type'] === "return"
            )
        ) {
            $route_type = $additional_data['route_type'];
        } else {
            return [
                'error' => "The route type is no valid."
            ];
        }
        $destination = false;
        $stops = [];
        foreach ($this->csvReaderRepository->getRows() as $row) {
            if ($route_type === 'going' && ($row['Stop type'] == 3 || $row['Stop type'] == "3")) {
                $destination = $row;
            } else if ($route_type === 'return' && ($row['Stop type'] == 1 || $row['Stop type'] == "1")) {
                $destination = $row;
            } else {
                $stops[] = $row;
            }
        }
        $taskId = $this->routeUpNewExecution->createNewExecution($destination, $stops, $additional_data);
        \Log::info($taskId);
        $this->routeUpTaskInfoRepository->saveTaskInfo($file_id, $taskId['task_id'], $user, json_encode($additional_data));
        return $taskId;
    }


}