<?php

namespace Core\Router\Infrastructure\Expeditions;

trait CurlRequestTrait
{
    protected function sendCurlRequest($data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->getEndpointUrl());
        $headers = $this->getHeaders();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $jsonEncodedData = json_encode($data);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonEncodedData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        if ($response === false) {
            $errorMessage = curl_error($curl);
            curl_close($curl);
            return 'Error en la solicitud cURL: ' . $errorMessage;
        }
        curl_close($curl);
        return json_decode($response, true);
    }
}

?>
