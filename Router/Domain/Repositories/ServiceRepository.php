<?php

namespace Core\Router\Domain\Repositories;

interface ServiceRepository
{
    public function getRouteByServiceId(int $service_id);
    public function getUTCDepartureTime(int $service_id);
    public function getReroutingConfig(int $service_id);
    public function canBeRerouted(int $service_id);
}