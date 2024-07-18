<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\RouteUpTaskInfoRepository;

class GetTaskInfoExecutionsTaskId
{

    public function __construct(
        private RouteUpTaskInfoRepository $routeUpTaskInfoRepository
    ){}

    public function __invoke($task_ids) {
        return $this->routeUpTaskInfoRepository->getTaskInfoForTaskIds($task_ids);
    }


}