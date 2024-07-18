<?php

namespace Core\Router\Infrastructure\Maps\Library;

class PolylineConversor
{

    #DOC: https://developers.google.com/maps/documentation/utilities/polylinealgorithm?hl=es-419

    function coordinateToPolyline($coords) {
        $polyline = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($coords as $coord) {
            $lat = $coord['latitude'];
            $lng = $coord['longitude'];

            $latE5 = round($lat * 1e5);
            $lngE5 = round($lng * 1e5);

            $latDiff = $latE5 - $prevLat;
            $lngDiff = $lngE5 - $prevLng;

            $polyline .= $this->encodeValue($latDiff) . $this->encodeValue($lngDiff);

            $prevLat = $latE5;
            $prevLng = $lngE5;
        }

        return $polyline;
    }

    private function encodeValue($value) {
        $encoded = '';

        $value = $value << 1;
        if ($value < 0) {
            $value = ~$value;
        }

        while ($value >= 0x20) {
            $chunk = ($value & 0x1F) | 0x20;
            $encoded .= chr($chunk + 63);
            $value >>= 5;
        }

        $encoded .= chr($value + 63);
        return $encoded;
    }
}
