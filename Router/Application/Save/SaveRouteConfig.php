<?php

namespace Core\Router\Application\Save;

use Core\Router\Domain\Repositories\RouterConfigRepository;

class SaveRouteConfig
{

    public function __construct(
        private RouterConfigRepository $routerConfig
    ){}

    public function __invoke(array $params, int $route_id) {
        return $this->routerConfig->saveRouterConfig($params, $route_id);
    }


}