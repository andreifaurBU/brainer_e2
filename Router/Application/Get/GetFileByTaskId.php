<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\RouteUpTaskInfoRepository;
use Core\Router\Domain\Repositories\RouteUpFileRepository;


class GetFileByTaskId
{

    public function __construct(
        private RouteUpTaskInfoRepository $routeUpTaskInfoRepository,
        private RouteUpFileRepository $routeUpFileRepository
    ){}

    public function __invoke(string $external_task_id) {
        $taskInfo = $this->routeUpTaskInfoRepository->getTaskInfoById($external_task_id);
        return $this->routeUpFileRepository->getRouteUpFile($taskInfo['file_id']);
    }


}