<?php

namespace App\Helpers;

class GeoHelper
{
    public static function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if ($lat1 == 0 || $lon1 == 0 || $lat2 == 0 || $lon2 == 0) {
            return 999999;
        }

        $earthRadius = 6371; // Jari-jari bumi (KM)

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public static function formatDistanceLabel(float $distanceInKm): string
    {
        if ($distanceInKm >= 999999) {
            return 'N/A';
        }

        return $distanceInKm < 1
            ? round($distanceInKm * 1000) . ' m away'
            : round($distanceInKm, 1) . ' km away';
    }
}