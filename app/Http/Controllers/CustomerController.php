<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RideHistory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function getCustomers()
    {
        $customers = User::where('role_id', User::ROLE_CUSTOMER)->get(['user_id', 'first_name', 'last_name', 'mobile_number', 'status']);
        return response()->json($customers);
    }

    public function updateStatus(Request $request, $user_id)
    {
        $request->validate([
            'status' => 'required|in:Active,Disabled',
        ]);

        $user = User::findOrFail($user_id);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user
        ]);
    }

    public function getCustomerById($user_id)
    {
        $user = User::where('role_id', User::ROLE_CUSTOMER)
            ->where('user_id', $user_id) // Ensure user_id is correct, assuming it's a valid column
            ->first(); // Fetch a single record

        // Check if the user exists
        if (!$user) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        // Check if the user's status is "Disabled"
        if ($user->status === 'Disabled') {
            return response()->json(['message' => 'Account Disabled'], 200);
        }

        // If condition is not met, return the rider's data
        return response()->json($user, 200);
    }


    public function book(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'fare' => 'required|numeric',
            'ride_type' => 'required',
        ]);

        $rideHistory = new RideHistory();
        $rideHistory->user_id = $validated['user_id'];
        $rideHistory->pickup_location = $validated['pickup_location'];
        $rideHistory->dropoff_location = $validated['dropoff_location'];
        $rideHistory->fare = $validated['fare'];
        $rideHistory->ride_date = now();
        $rideHistory->ride_type = $validated['ride_type'];
        $rideHistory->status = 'Available';
        $rideHistory->save();

        return response()->json(['success' => true, 'ride_id' => $rideHistory->ride_id], 201);
    }



    public function checkActiveRide($user_id)
    {
        $activeRide = RideHistory::where('user_id', $user_id)
            ->whereIn('status', ['Available', 'Booked', 'In Transit', 'Review'])
            ->with(['user', 'rider'])
            ->latest()
            ->first();

        return response()->json([
            'hasActiveRide' => $activeRide !== null,
            'rideDetails' => $activeRide
        ]);
    }
    

    public function cancelRide(Request $request, $ride_id)
    {
        $ride = RideHistory::find($ride_id);
    
        if (!$ride || $ride->status == 'Canceled') {
            return response()->json(['error' => 'This ride is no longer available or cannot be canceled'], 400);
        }
    
        // Logic to cancel the ride
        $ride->status = 'Canceled';
        $ride->save();
    
        return response()->json(['message' => 'Ride successfully canceled']);
    }

    public function finish_ride(Request $request, $ride_id)
    {
        $ride = RideHistory::find($ride_id);
    
        if (!$ride || $ride->status == 'Canceled') {
            return response()->json(['error' => 'This ride is no longer available.'], 400);
        }
    
        // Logic to cancel the ride
        $ride->status = 'Completed';
        $ride->save();
    
        return response()->json(['message' => 'Ride successfully ended']);
    }

}