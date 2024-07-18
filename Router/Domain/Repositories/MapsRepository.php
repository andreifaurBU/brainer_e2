<?php

namespace Core\Router\Domain\Repositories;

interface MapsRepository
{
    public function calculateRoute(array $options);
}