<?php

namespace Core\Router\Infrastructure\Expeditions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait GuzzleRequestTrait
{
    protected function sendGuzzleRequest($data)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($this->getEndpointUrl(), [
                'headers' => $this->getHeaders(),
                'form_params' => $data
            ]);
            return $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                return 'Error en la solicitud Guzzle: ' . $e->getResponse()->getBody()->getContents();
            } else {
                return 'Error en la solicitud Guzzle: ' . $e->getMessage();
            }
        }
    }
}

?>
