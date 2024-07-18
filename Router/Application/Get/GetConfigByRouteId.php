<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\RouterConfigRepository;

class GetConfigByRouteId
{

    public function __construct(
        private RouterConfigRepository $routerConfig
    ){}

    public function __invoke(int $route_id) {
        return $this->routerConfig->getRouteConfigById($route_id);
    }


}