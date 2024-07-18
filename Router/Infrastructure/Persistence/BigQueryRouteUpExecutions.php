<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\RouteUpExecutionsRepository;
use Core\Router\Infrastructure\Persistence\Libraries\BigQueryLibrary;

class BigQueryRouteUpExecutions implements RouteUpExecutionsRepository
{

    private string $table = "route_optimizer";
    private int $defaultPerPage = 10;
    private string $dataset;

    private BigQueryLibrary $bigQueryLibrary;

    public function __construct()
    {
        $this->dataset = env('DB_BQ_DATASET');
        $this->bigQueryLibrary = new BigQueryLibrary($this->table);
    }

    public function getExecutionByTaskId(string $task_id){
        $sql = "SELECT * FROM `" . $this->dataset . "." . $this->table . "` WHERE task_id ='$task_id'";
        return $this->bigQueryLibrary->select($sql);
    }

    public function getAllExecutions(array $filters, int $page = 1){

        $sql = "SELECT id, task_id, event, status, created, updated, owner_id FROM `" . $this->dataset . "." . $this->table . "` ORDER BY created DESC";

        $this->bigQueryLibrary->setPerPage(
            $this->getPerPageParam($filters)
        );

        $pagination = $this->bigQueryLibrary->getPagination(
            "SELECT count(*) as total FROM `" . $this->dataset . "." . $this->table . "`"
        );
        $pagination['current_page'] = $page;

        return [
            'results' => $this->bigQueryLibrary->select($sql, $page),
            'pagination' => $pagination
        ];
    }

    private function getPerPageParam(array $filters): int {
        if (isset($filters['perPage'])) return (int) $filters['perPage'];
        return $this->defaultPerPage;
    }
}
