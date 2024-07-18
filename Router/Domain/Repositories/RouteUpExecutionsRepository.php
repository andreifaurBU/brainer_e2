<?php

namespace Core\Router\Domain\Repositories;

interface RouteUpExecutionsRepository
{
    public function getAllExecutions(array $filters, int $page);
    public function getExecutionByTaskId(string $task_id);
}
