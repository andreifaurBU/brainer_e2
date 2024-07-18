<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\ServiceRepository;
use Core\Router\Infrastructure\Persistence\Model\ServiceModel;


class EloquentServiceRepository implements ServiceRepository
{
    public function getRouteByServiceId(int $service_id)
    {
        $service = ServiceModel::where('id', $service_id)
            ->select(
                'external_route_id',
                'track_id',
                'plan_id',
                'return',
                'status',
                'departure_timestamp'
            )->first();
        return $service;
    }

    public function canBeRerouted(int $service_id)
    {
        $service = ServiceModel::where('id', $service_id)
        ->whereNotIn('status', [
            ServiceModel::IN_PROGRESS,
            ServiceModel::IN_PROGRESS_WITH_ISSUES,
            ServiceModel::CANCELED
        ])->first();
        return $service !== null; 
    }

    public function getUTCDepartureTime(int $service_id) {

        $service = ServiceModel::where('services.id', $service_id)
                    ->join(env('DB_DATABASE') . '.provinces as p', 'p.id', '=', 'province_id')
                    ->select(
                        'services.id',
                        'external_route_id',
                        'track_id',
                        'p.timezone',
                        'return',
                        'status',
                        'departure_timestamp'
                    )
                    ->first();
        return $service;
    }

    public function getReroutingConfig(int $service_id)
    {
        $url = env('BUSUP_API_APP_DASHBOARD_URL') . "/services/$service_id/getConfigurationService";
        $response = file_get_contents($url);
        $responseData = json_decode($response, true);
        return $responseData['data'];
    }

}