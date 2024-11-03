<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rider;
use App\Models\RideHistory;
use App\Models\RideLocation;

class RidesService
{
    public function getAvailableRides()
    {
        $availableRides = RideHistory::where('ride_histories.status', 'Available') 
            ->join('users', 'ride_histories.user_id', '=', 'users.user_id')
            ->select('ride_histories.*', 'users.first_name', 'users.last_name')
            ->orderBy('ride_histories.created_at', 'desc')
            ->with(['user', 'rideLocations'])
            ->get();
    
        return ($availableRides);
    }
}
