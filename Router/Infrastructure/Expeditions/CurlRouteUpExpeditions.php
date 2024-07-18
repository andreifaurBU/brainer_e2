<?php

namespace Core\Router\Infrastructure\Expeditions;
use Core\Router\Domain\Repositories\RouteUpNewExecution;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class CurlRouteUpExpeditions implements RouteUpNewExecution
{
    use CurlRequestTrait;

    public function createNewExecution($destination, $stops, $additional_data) {
        $cartography_provider = "valhalla";
        $mode = $additional_data['route_type'] === 'going' ? 'inbound' : 'outbound';
        $depot_lat = $destination['Stop latitude'];
        $depot_lng =  $destination['Stop longitude'];
        $depot_time =  date("Y-m-d") ." ". $additional_data['time'];
        $fleet = [];
        $formattedStops = [];
        foreach ($stops as $stop) {
            $passengers = explode(",", $stop['Passenger list']);
            $passengersObjects = [];
            foreach ($passengers as $passenger) {
                $passengersObjects[] = [
                    'id' => (int) $passenger
                ];
            }
            $formattedStops[] = [
                'id' => $stop['Stop id'],
                'lat' => $stop['Stop latitude'],
                'lng' => $stop['Stop longitude'],
                'number_of_reservations' => $stop['Number of passengers'],
                'passengers' => $passengersObjects
            ];
        }
        $max_travel_time = $additional_data['max_route_time'];

        $advanced_configuration = is_array($additional_data['advanced_configuration']) ?
            $additional_data['advanced_configuration']
            : json_decode($additional_data['advanced_configuration'], true);

        $slack_time = $advanced_configuration['slack_time'];
        $local_search = $advanced_configuration['local_search'];
        $max_time_search = $advanced_configuration['max_time_search'];
        $vehicle_penalty = $advanced_configuration['vehicle_penalty'];

        $fleet_configuration = is_array($additional_data['fleet_configuration']) ?
            $additional_data['fleet_configuration']
            : json_decode($additional_data['fleet_configuration'], true);


        $payload = [
            'mode' => $mode,
            'cartography_provider' => $cartography_provider,
            'depot_lat' => $depot_lat,
            'depot_lng' => $depot_lng,
            'depot_time' => $depot_time,
            'stops' => $formattedStops,
            'fleet' => $fleet_configuration,
            'max_travel_time' => $max_travel_time * 60,
            'slack_time' => $slack_time * 60,
            'local_search' => false,
            'max_time_search' => $max_time_search * 60,
            'vehicle_penalty' => $vehicle_penalty
        ];
        return $this->sendCurlRequest($payload);
    }

    protected function getBrainerHost()
    {
        return env('BRAINER_ROUTEUP_HOST', 'brainer.busup.org');
    }

    protected function getBrainerProtocol()
    {
        return env('BRAINER_ROUTEUP_PROTOCOL', 'https');
    }

    protected function getEndpointUrl()
    {
        $host = $this->getBrainerHost();
        $protocol = $this->getBrainerProtocol();
        return "$protocol://$host/api/v1/route_optimizer/route_optimizer_send_task/";
    }

    protected function getHeaders()
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getBearerToken(),
            'Accept: application/json'
        ];
    }

    protected function getAuthEndpointUrl()
    {
        $host = $this->getBrainerHost();
        $protocol = $this->getBrainerProtocol();
        return "$protocol://$host/api/v1/login/access-token/";
    }

    protected function getBearerToken()
    {
        $cacheKey = "routeup_bearer_access_token";
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($this->getAuthEndpointUrl(), [
                'headers' => [],
                'form_params' => [
                    'username' => env('ROUTE_UP_USERNAME'),
                    'password' => env('ROUTE_UP_PASS')
                ]
            ]);
            $access_token = json_decode($response->getBody()->getContents())->access_token;
            Cache::put(
                $cacheKey,
                $access_token,
                now()->addHours(6)
            );
            return Cache::get($cacheKey);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return 'Error en la solicitud Guzzle: ' . $e->getResponse()->getBody()->getContents();
            } else {
                return 'Error en la solicitud Guzzle: ' . $e->getMessage();
            }
        }

    }
}
