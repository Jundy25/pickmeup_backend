<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RideHistory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Events\RidesUpdated;
use App\Events\DashboardUpdated;

use App\Services\DashboardService;

class CustomerController extends Controller
{

    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }


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
        $user = User::where('user_id', $user_id) // Ensure user_id is correct, assuming it's a valid column
            ->first(); // Fetch a single record

        // Check if the user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
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

        // Fetch all available rides to send in the event
        $rides = RideHistory::where('ride_histories.status', 'Available') 
            ->join('users', 'ride_histories.user_id', '=', 'users.user_id')
            ->select('ride_histories.*', 'users.first_name', 'users.last_name')
            ->orderBy('ride_histories.created_at', 'desc')
            ->with(['user', 'ridelocations'])
            ->get();
        // Dispatch the RidesUpdated event with the available rides
        event(new RidesUpdated($rides));

        // Fetch updated counts and bookings using DashboardService
        $data = $this->dashboardService->getCounts();
        $counts = $data['counts'];
        $bookings = $data['bookings'];

        event(new DashboardUpdated($counts, $bookings));
    
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


    public function viewApplications($user_id)
    {
        $activeRide = RideApplication::where('ride_id', $ride_id)
            ->whereIn('status', ['Available', 'Booked', 'In Transit', 'Review'])
            ->with(['ridehistory', 'rider'])
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

        // Fetch all available rides to send in the event
        $rides = RideHistory::where('ride_histories.status', 'Available') 
            ->join('users', 'ride_histories.user_id', '=', 'users.user_id')
            ->select('ride_histories.*', 'users.first_name', 'users.last_name')
            ->orderBy('ride_histories.created_at', 'desc')
            ->with(['user', 'ridelocations'])
            ->get();

        // Dispatch the RidesUpdated event with the available rides
        event(new RidesUpdated($rides));

        // Fetch updated counts and bookings using DashboardService
        $data = $this->dashboardService->getCounts();
        $counts = $data['counts'];
        $bookings = $data['bookings'];
        
        event(new DashboardUpdated($counts, $bookings));
    
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