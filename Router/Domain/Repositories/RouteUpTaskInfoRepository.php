<?php

namespace Core\Router\Domain\Repositories;

interface RouteUpTaskInfoRepository
{
    public function saveTaskInfo($file_id, $external_task_id, $user, $constraints);
    public function getTaskInfoById($external_task_id);
    public function getTaskInfoForTaskIds(array $task_ids);
}