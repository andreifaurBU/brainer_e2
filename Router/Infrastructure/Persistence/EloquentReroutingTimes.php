<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\ReroutingTimesRepository;
use Core\Router\Infrastructure\Persistence\Model\ReroutingTimes;

class EloquentReroutingTimes implements ReroutingTimesRepository
{
    public function saveScheduleService(array $schedule, int $service_id) {

        $reroutingTimes = ReroutingTimes::where('service_id', $service_id)->first();

        if (is_null($reroutingTimes)) {
            $reroutingTimes = new ReroutingTimes();
        }

        $reroutingTimes->route_id = $schedule['route_id'];
        $reroutingTimes->service_id = $service_id;
        $reroutingTimes->expected_time = $schedule['expected_utc_time'];
        $reroutingTimes->status = 0;
        $reroutingTimes->save();
    
    }

    public function getServicesByExecutionTime($time) {
        // Convertir el DateTime a una cadena de hora en formato 'Y-m-d H:i'
        $formattedTime = $time->format('Y-m-d H:i');
    
        // Obtener los servicios cuya hora esperada coincide con la hora proporcionada (ignorando los segundos)
        $services = ReroutingTimes::whereRaw("DATE_FORMAT(expected_time, '%Y-%m-%d %H:%i') = ?", [$formattedTime])
                                   ->get();
    
        return $services;
    }

    public function updateScheduleService(array $schedule, int $service_id) {
        $reroutingTimes = ReroutingTimes::where('service_id', $service_id)->first();
        if (!is_null($reroutingTimes)) {
            $reroutingTimes->executed_time = now()->utc();
            $reroutingTimes->status = 1;
            if (isset($schedule['not_rerouting_reason'])) {
                $reroutingTimes->not_rerouting_reason =  $schedule['not_rerouting_reason'];
            }
            if (isset($schedule['raw_response'])) {
                $reroutingTimes->raw_response = $schedule['raw_response'];
            }
            if (isset($schedule['processed_response'])) {
                $reroutingTimes->processed_response = $schedule['processed_response'];
            }
            $reroutingTimes->save();
        }
        return $reroutingTimes;
    }

}