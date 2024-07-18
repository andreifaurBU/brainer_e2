<?php

namespace Core\Router\Domain\Repositories;

interface StopRepository
{
    public function getStopsByIds(array $ids);

}
