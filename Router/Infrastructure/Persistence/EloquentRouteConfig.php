<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\RouterConfigRepository;
use Core\Router\Infrastructure\Persistence\Model\RouteConfig;
use DateTime;
use DateInterval;

class EloquentRouteConfig implements RouterConfigRepository
{

    public function getRouteConfigById(int $route_id)
    {
        $config = RouteConfig::where('route_id', $route_id)
            ->first();
        if (!is_null($config)) {
            $config['time_data'] = json_decode($config['time_data']);
            $config['can_see_rerouting_configuration'] = true;
        }
        $config['can_see_rerouting_configuration'] = true;
        return $config;
    }

    public function saveRouterConfig(array $config, int $route_id)
    {
        $routeConfig = RouteConfig::where('route_id', $route_id)
            ->first();
        
        if (is_null($routeConfig)) {
            $routeConfig = new RouteConfig();
            $routeConfig->route_id = $route_id;
            
        }
        $routeConfig->activated = isset($config['activated']) ? $config['activated'] : true;
        $routeConfig->type = $config['type'];
        $routeConfig->time_data = json_encode($config['time_data']);
        return $routeConfig->save();
    }

}