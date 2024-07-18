<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\RouteUpExecutionsRepository;

class GetRouteUpExecutions
{

    public function __construct(
        private RouteUpExecutionsRepository $routeUpExecutionsRepository
    ){}

    public function __invoke(array $filters, int $page = 1) {
        return $this->routeUpExecutionsRepository->getAllExecutions($filters, $page);
    }


}