<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\StopRepository;
use Core\Router\Infrastructure\Persistence\Model\RouteStopModel;


class EloquentStopRepository implements StopRepository
{
    public function getStopsByIds(array $ids)
    {
        return RouteStopModel::whereIn('id', $ids)->get();
    }


}
