<?php

namespace Core\Router\Application\Execute;

use Core\Router\Domain\Repositories\ServiceRepository;
use Core\Router\Domain\Repositories\ReroutingTimesRepository;
use Core\Router\Domain\Repositories\MapsRepository;
use Core\Router\Domain\Repositories\ExpeditionsRepository;

class ExecuteRerouting
{

    public function __construct(
        private ServiceRepository $serviceRepository,
        private ReroutingTimesRepository $reroutingTimesRepository,
        private MapsRepository $mapsRepository,
        private ExpeditionsRepository $expeditionsRepository
    ){}

    public function __invoke(int $service_id) {
        try {
            $config = $this->serviceRepository->getReroutingConfig($service_id);
            if ($config['return'] !== 1) {
                return [
                    'error' => "Only return routes"
                ];
            }
            if (!$this->serviceRepository->canBeRerouted($service_id)) {
                $reason =  [
                    'error' => "The service can't be rerouted because of the status of the service."
                ];
                $this->reroutingTimesRepository->updateScheduleService([
                    'not_rerouting_reason' => json_encode($reason)
                ], $service_id);
                return $reason;
            }
            $result = $this->mapsRepository->calculateRoute($config);
            if (isset($result['response']['error_message'])) {
                $this->reroutingTimesRepository->updateScheduleService([
                    'not_rerouting_reason' => json_encode($result['response'])
                ], $service_id);
                return $result;
            }
            $polyline = $result['response']['routes'][0]['overview_polyline']['points'];
            $processed_response = [
                'pax' => $config['pax'],
                'occupation' => $config['occupation'],
                'service' => $service_id,
                'stops' => $result['stops'],
                'polyline_raw' => $polyline
            ];
            $savedExpedition = $this->expeditionsRepository->saveNewExpedition($processed_response);
            $this->reroutingTimesRepository->updateScheduleService([
                'raw_response' => json_encode($result['response']),
                'processed_response' => json_encode($processed_response)
            ], $service_id);
            return $savedExpedition;
        } catch (\Throwable $th) {
            dd($th);
        }

    }


}