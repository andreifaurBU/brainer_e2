<?php

namespace Core\Router\Domain\Repositories;

interface RouteUpNewExecution
{
    public function createNewExecution($destination, $stops, $additional_data);
}