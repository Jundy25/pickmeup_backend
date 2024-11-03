<?php

namespace App\Services;

class FareService
{
    private const BASE_FARE = 40;
    private const ADDITIONAL_FARE_RATE = 10;
    private const THRESHOLD_KM = 2;

    public function calculateFare(float $distance): float
    {
        if ($distance <= self::THRESHOLD_KM) {
            return self::BASE_FARE;
        }

        $exceedingDistance = $distance - self::THRESHOLD_KM;
        return round(self::BASE_FARE + ($exceedingDistance * self::ADDITIONAL_FARE_RATE), 2);
    }
}