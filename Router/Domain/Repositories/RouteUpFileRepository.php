<?php

namespace Core\Router\Domain\Repositories;

interface RouteUpFileRepository
{
    public function saveRouteUpFile($file);
    public function getRouteUpFile($file_id);
}