<?php

namespace Core\Router\Infrastructure\Expeditions;
use Core\Router\Domain\Repositories\ExpeditionsRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class GuzzleExpeditionsRepository implements ExpeditionsRepository
{

    use GuzzleRequestTrait;

    public function saveNewExpedition(array $expedition)
    {
        $data = [
            'service' => $expedition['service'],
            'stops' => $expedition['stops'],
            'pax' => $expedition['pax'],
            'occupation' => $expedition['occupation'],
            'need_excel' => 0,
            'polyline_raw' => [$expedition['polyline_raw']],
            'from' => 'automatic'
        ];
        return $this->sendGuzzleRequest($data);
    }

    protected function getEndpointUrl()
    {
        return env('BUSUP_API_APP_DASHBOARD_URL') . '/services/setConfigurationService';
    }

    protected function getAuthEndpointUrl()
    {
        return env('BUSUP_API_APP_DASHBOARD_URL') . '/auth/loginPlatform';
    }

    protected function getHeaders()
    {
        return [
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: Bearer ' . $this->getBearerToken(),
            'Accept: application/json'
        ];
    }

    protected function getBearerToken()
    {
        $cacheKey = "router_bearer_access_token";
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($this->getAuthEndpointUrl(), [
                'headers' => [],
                'form_params' => []
            ]);
            $access_token = json_decode($response->getBody()->getContents())->data->authorization->access_token;
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