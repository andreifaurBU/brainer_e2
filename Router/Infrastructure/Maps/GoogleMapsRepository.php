<?php

namespace Core\Router\Infrastructure\Maps;
use Core\Router\Domain\Repositories\MapsRepository;

class GoogleMapsRepository implements MapsRepository
{

    public function calculateRoute(array $options) {

        $origin = null;
        $wayPoints = [];
        $destination = null;
        $rawStops = [];

        if ($options['return'] === 1) {
            
            foreach ($options['stops'] as $stop) {
                if (isset($stop['departure'])) {
                    $stop['departure_time'] = $stop['departure'];
                }
                if (isset($stop['name'])) {
                    $stop['address'] = $stop['name'];
                }
                if ($stop['stop_type_id'] === 2) {
                    if ($stop['occupation_destination'] > 0 || $stop['occupation_origin'] > 0) {
                        $wayPoints[] = $stop['lat'] . "," . $stop['lng'];
                        $rawStops[] = $stop;
                    }  
                } else if ($stop['stop_type_id'] === 1) {
                    $origin = $stop['lat'] . "," . $stop['lng'];
                    $rawStops[] = $stop;
                } else if ($stop['stop_type_id'] === 3) {
                    if ($stop['occupation_destination'] > 0) {
                        $destination = $stop['lat'] . "," . $stop['lng'];
                        $rawStops[] = $stop;
                    }
                }
            }

            $rawStops[count($rawStops) - 1]['stop_type_id'] = 3;


            if (is_null($destination)) {
                $destination = array_pop($wayPoints);
            }

            $transitOptions['departureTime'] = $this->getDepartureTime($options['stops'][0]);

            $gmaps = new \yidas\googleMaps\Client(['key'=> env('API_KEY_GOOGLE_MAPS')]);

            $payload = [
                'mode' => "DRIVING",
                'waypoints' => implode('|', $wayPoints),
                'optimizeWaypoints' => false,
                'departure_time' => $transitOptions['departureTime']
            ];

            $response = $gmaps->directions($origin, $destination, $payload);
            return [
                'stops' => $this->getTimesForStops($response, $rawStops),
                'response' => $response
            ];

        } else {
            return [
                'error' => "Only return routes can be rerouted."
            ];
        }

    }

    private function getTimesForStops($response, $stops){
        if ($response['status'] == 'OK') {
            $stopTimeSec = $this->calculateTimeDifference($stops[0]['arrival_time'], $stops[0]['departure_time']);
            foreach ($response['routes'][0]['legs'] as $position => $leg) {
                $stops[$position+1]['arrival_time'] = $this->getNewTime($stops[$position]['departure_time'], $leg['duration']['value']);
                $stops[$position+1]['departure_time'] = $this->getNewTime($stops[$position+1]['arrival_time'], $stopTimeSec);
            }
        }
        return $stops;
    }

    private function getNewTime($time_str, $secs) {
        $hour = \DateTime::createFromFormat('H:i', $time_str);
        $extra_mins = ceil($secs / 60);  
        $hour->add(new \DateInterval('PT' . $extra_mins . 'M'));
        $final_hour_str = $hour->format('H:i');
        return $final_hour_str;
    }

    private function getDepartureTime($origin) {
        $currentDateTime = time();
        $extraDaysDeparture = isset($origin['extra_days_departure']) ? $origin['extra_days_departure'] : 0;
        $departureTime = isset($origin['departure_time']) ? $origin['departure_time'] : 'now';
        $departureDateTime = $departureTime === 'now' ? $currentDateTime : strtotime("+ $extraDaysDeparture days", $currentDateTime);
        //return date('Y-m-d\TH:i:s', $departureDateTime);
        return $departureDateTime;
    }

    private function calculateTimeDifference(string $time1, string $time2): int {
        $timestamp1 = strtotime($time1);
        $timestamp2 = strtotime($time2);
        $difference = abs($timestamp2 - $timestamp1);
        return $difference;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Earth radius
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance;
    }

}