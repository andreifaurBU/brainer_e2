<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\RouteUpTaskInfoRepository;
use Core\Router\Infrastructure\Persistence\Model\RouteUpTaskInfo;


class EloquentRouteUpTaskInfoRepository implements RouteUpTaskInfoRepository
{
    public function saveTaskInfo($file_id, $external_task_id, $user, $constraints) {
        $taskInfo = new RouteUpTaskInfo();
        $taskInfo->file_id = $file_id;
        $taskInfo->external_task_id = $external_task_id;
        $taskInfo->user = $user;
        $taskInfo->constraints = $constraints;

        $taskInfo->save();
        return $taskInfo;
    }

    public function getTaskInfoById($external_task_id)
    {
        return RouteUpTaskInfo::where('external_task_id', $external_task_id)->first();
    }

    public function getTaskInfoForTaskIds(array $task_ids) {
        return RouteUpTaskInfo::whereIn('external_task_id', $task_ids)->get();
    }
}