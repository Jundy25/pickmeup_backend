<?php

namespace App\Http\Controllers;

use App\Models\RideLocation;
use App\Models\RideHistory;
use Illuminate\Http\Request;

class RideController extends Controller
{

    public function saveBookLocation(Request $request)
    {
        $ride = RideHistory::where('user_id', $request->user_id)
            ->latest()
            ->first();
        
        if (!$ride) {
            return response()->json(['message' => 'Ride not found'], 404);
        }

        $rideLocation = new RideLocation();
        $rideLocation->ride_id = $ride->ride_id;
        $rideLocation->customer_latitude = $request->customer_latitude;
        $rideLocation->customer_longitude = $request->customer_longitude;
        $rideLocation->dropoff_latitude = $request->dropoff_latitude;
        $rideLocation->dropoff_longitude = $request->dropoff_longitude;
        $rideLocation->save();

        return response()->json(['message' => 'Ride location saved successfully']);
    }

    public function setRiderLocation(Request $request)
    {
        $rideLocation = RideLocation::where('ride_id', $request->ride_id)
        ->latest() // Order by latest
        ->first(); // Fetch the latest record
    
        if (!$rideLocation) {
            return response()->json(['message' => 'Ride location not found'], 404);
        }
    
        $rideLocation->rider_latitude = $request->rider_latitude;
        $rideLocation->rider_longitude = $request->rider_longitude;
        $rideLocation->save();
    
        return response()->json(['message' => 'Rider location updated successfully']);
    }

    // public function getRideLocation(Request $request)
    // {
    //     $ride = RideLocation::where('ride_id', $request->user_id)
    //         ->latest()
    //         ->first();
        
    //     if (!$ride) {
    //         return response()->json(['message' => 'Ride not found'], 404);
    //     }

    //     $rideLocation = new RideLocation();
    //     $rideLocation->ride_id = $ride->ride_id;
    //     $rideLocation->customer_latitude = $request->customer_latitude;
    //     $rideLocation->customer_longitude = $request->customer_longitude;
    //     $rideLocation->dropoff_latitude = $request->dropoff_latitude;
    //     $rideLocation->dropoff_longitude = $request->dropoff_longitude;
    //     $rideLocation->save();

    //     return response()->json(['message' => 'Ride location saved successfully']);
    // }

   
}
