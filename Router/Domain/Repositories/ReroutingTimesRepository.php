<?php

namespace Core\Router\Domain\Repositories;

interface ReroutingTimesRepository
{
    public function saveScheduleService(array $schedule, int $service_id);
    public function updateScheduleService(array $schedule, int $service_id);
    public function getServicesByExecutionTime($time);
}