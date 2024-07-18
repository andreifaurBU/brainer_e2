<?php

namespace Core\Router\Application\Schedule;

use Core\Router\Domain\Repositories\ServiceRepository;
use Core\Router\Application\Get\GetConfigByRouteId;
use Core\Router\Domain\Repositories\ReroutingTimesRepository;

class ScheduleServiceExecution
{

    public function __construct(
        private ServiceRepository $serviceRepository,
        private GetConfigByRouteId $getConfigByRouteId,
        private ReroutingTimesRepository $reroutingTimesRepository

    ){}

    public function __invoke(int $service_id, $routeConfig = null) {

        $service = $this->serviceRepository->getUTCDepartureTime($service_id);

        if (!is_null($service)) {

            if (is_null($routeConfig)) {
                $routeConfig = $this->getConfigByRouteId->__invoke(abs($service['external_route_id']));
            }

            if (
                isset($routeConfig['route_id'])
                && isset($routeConfig['type'])
                && isset($routeConfig['time_data'])
                && ($routeConfig['type'] === 'time_before_service')
                && isset($routeConfig['time_data']->quantity)
                && isset($routeConfig['time_data']->type_quantity)
            ) {
                $minutesBefore = 0;
                switch ($routeConfig['time_data']->type_quantity) {
                    case 'days':
                        $minutesBefore =  intval($routeConfig['time_data']->quantity) * 60 * 24;
                        break;
                    case 'hours':
                        $minutesBefore =  intval($routeConfig['time_data']->quantity) * 60;
                        break;
                    case 'minutes':
                        $minutesBefore =  intval($routeConfig['time_data']->quantity);
                        break;
                }
            }
            $departureDateTime = new \DateTime($service['departure_timestamp'], new \DateTimeZone($service['timezone']));

            $departureDateTime->modify("-$minutesBefore minutes");
            $departureDateTime->setTimezone(new \DateTimeZone('UTC'));
            $departureTimestampUTC = $departureDateTime->format('Y-m-d H:i');

            $currentDateTimeUTC = new \DateTime('now', new \DateTimeZone('UTC'));

            if (
                (new \DateTime($departureTimestampUTC, new \DateTimeZone('UTC'))) > (new \DateTime('now', new \DateTimeZone('UTC')))
            ) {
                $this->reroutingTimesRepository->saveScheduleService([
                    'expected_utc_time' => $departureTimestampUTC,
                    'route_id' => $routeConfig['route_id']
                ], $service_id);
            }
        }

        return $service;
    }


}