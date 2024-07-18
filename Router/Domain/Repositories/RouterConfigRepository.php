<?php

namespace Core\Router\Domain\Repositories;

interface RouterConfigRepository
{
    public function saveRouterConfig(array $config, int $route_id);
    public function getRouteConfigById(int $route_id);
}