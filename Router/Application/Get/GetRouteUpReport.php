<?php

namespace Core\Router\Application\Get;

use Core\Router\Domain\Repositories\RouteUpReportsRepository;

class GetRouteUpReport
{

    public function __construct(
        private RouteUpReportsRepository $reportsRepository
    ) {
    }

    public function __invoke(
        array $route_codes,
        string $site,
        string $arrival_time_from,
        string $arrival_time_to,
        string $date_from,
        string $date_to,
        string $route_type
    ): array
    {
        $rows = match ($route_type) {
            'going' => $this->reportsRepository->getReportGoing(
                $route_codes,
                $site,
                $arrival_time_from,
                $arrival_time_to,
                $date_from,
                $date_to
            ),
            'return' => $this->reportsRepository->getReportReturn(
                $route_codes,
                $site,
                $arrival_time_from,
                $arrival_time_to,
                $date_from,
                $date_to
            ),
        };

        return $this->getFormattedRows($rows, $route_type);
    }

    private function getFormattedRows(array $rows, string $route_type): array
    {
        if (count($rows) === 0){
            return [];
        }

        $formatted_rows = [];

        if ($route_type === 'return'){
            $formatted_rows[] = [
                'stop_id' => 0,
                'stop_type' => 1,
                'stop_latitude' => $rows[0]['site_lat'],
                'stop_longitude' => $rows[0]['site_lng'],
                'passenger_list' => null,
                'number_of_passengers' => null,
            ];
        }

        foreach ($rows as $r){
            $formatted_rows[] = [
                'stop_id' => $r['id'],
                'stop_type' => 2,
                'stop_latitude' => $r['lat_stop'],
                'stop_longitude' => $r['lng_stop'],
                //'passenger_list' => $r['users_agg'],
                //In order to avoid big lists of passengers and since we are not using it yet, we're setting it to void list.
                'passenger_list' => "",
                'number_of_passengers' => intval($r['f0_']),
            ];
        }

        if ($route_type === 'going'){
            $formatted_rows[] = [
                'stop_id' => 0,
                'stop_type' => 3,
                'stop_latitude' => $rows[0]['site_lat'],
                'stop_longitude' => $rows[0]['site_lng'],
                'passenger_list' => null,
                'number_of_passengers' => null,
            ];
        }

        return $formatted_rows;
    }

}
