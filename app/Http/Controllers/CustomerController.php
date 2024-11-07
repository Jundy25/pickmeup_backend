<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rider;
use App\Models\RideHistory;
use App\Models\RideLocation;
use App\Models\RideApplication;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Events\RidesUpdated;
use App\Events\DashboardUpdated;
use App\Events\RideApply;
// use App\Events\NewNotification;


use App\Services\DashboardService;
use App\Services\RidesService;
use App\Services\FareService;
// use App\Services\NotificationService;

class CustomerController extends Controller
{

    protected $dashboardService;
    protected $ridesService;
    protected $fareService;

        

    public function __construct(
        DashboardService $dashboardService, 
        RidesService $ridesService,
        FareService $fareService)
        // NotificationService $notificationService
        
    {
        $this->dashboardService = $dashboardService;
        $this->ridesService = $ridesService;
        $this->fareService = $fareService;
        // $this->notificationService = $notificationService;
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
            'distance' => 'required',
            'ride_type' => 'required',
        ]);

        // Calculate fare based on distance
        $calculatedFare = $this->fareService->calculateFare($validated['distance']);
    
        $rideHistory = new RideHistory();
        $rideHistory->user_id = $validated['user_id'];
        $rideHistory->pickup_location = $validated['pickup_location'];
        $rideHistory->dropoff_location = $validated['dropoff_location'];
        $rideHistory->fare = $validated['fare'];
        $rideHistory->distance = $validated['distance'];
        $rideHistory->calculated_fare = round($calculatedFare, 2);;
        $rideHistory->ride_date = now();
        $rideHistory->ride_type = $validated['ride_type'];
        $rideHistory->status = 'Available';
        $rideHistory->save();

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
            ->join('ride_locations', 'ride_histories.ride_id', '=', 'ride_locations.ride_id')
            ->select(
                'ride_histories.*',
                'ride_locations.customer_latitude',
                'ride_locations.customer_longitude',
                'ride_locations.dropoff_latitude',
                'ride_locations.dropoff_longitude'
            )
            ->with(['user', 'rider'])
            ->latest()
            ->first();

        return response()->json([
            'hasActiveRide' => $activeRide !== null,
            'rideDetails' => $activeRide
        ]);
    }


    public function viewApplications(Request $request)
    {
        $applications = RideApplication::where('ride_id', $request->input('ride_id'))
            ->where('status', 'Pending')
            ->with('ridehistory')
            ->latest()
            ->get()
            ->map(function ($application) {
                // Fetch only first_name and last_name for the applier
                $applierDetails = \DB::table('users')
                    ->select('user_id', 'first_name', 'last_name')
                    ->where('user_id', $application->applier)
                    ->first();
                $applierLoc = \DB::table('riders')
                    ->select('rider_latitude', 'rider_longitude')
                    ->where('user_id', $application->applier)
                    ->first();

                $application->applier_details = $applierDetails;
                $application->applier_loc = $applierLoc;

                return $application;
            });

        \Log::info("Applications for ride_id {$request->input('ride_id')}: " . json_encode($applications));
        return response()->json($applications);
    }


    // public function accept_ride(Request $request, $ride_id)
    // {
    //     Log::info("Attempting to accept ride with ID: " . $ride_id);

    //     try {
    //         return DB::transaction(function () use ($ride_id, $request) {
    //             // Validate that user_id is provided
    //             $validated = $request->validate([
    //                 'user_id' => 'required|exists:users,user_id',
    //             ]);

    //             $user_id = $validated['user_id'];

    //             $application = RideApplication::where('ride_id', $ride_id)
    //                             ->where('status', 'Matched')
    //                             ->lockForUpdate()
    //                             ->first();
    //             if (!$application) {
    //                 Log::warning("Ride not available for acceptance: " . $ride_id);
    //                 return response()->json(['message' => 'This ride is no longer available.'], 200);
    //             }     
    //             $application->status = 'Matched';
    //             $application->save();   

    //             $ride = RideHistory::where('ride_id', $ride_id)
    //                             ->where('status', 'Available')
    //                             ->lockForUpdate()
    //                             ->first();

    //             if (!$ride) {
    //                 Log::warning("Ride not available for acceptance: " . $ride_id);
    //                 return response()->json(['message' => 'This ride is no longer available.'], 200);
    //             }
                
    //             // Update the ride status and assign the rider_id
    //             $ride->status = 'Booked';
    //             $ride->rider_id = $user_id;
    //             $ride->save();

    //             event(new RidesBooked($ride));



    //             Log::info("Ride accepted successfully: " . $ride_id);
    //             return response()->json(['message' => 'Ride Accepted Successfully.']);
    //         });
    //     } catch (\Exception $e) {
    //         Log::error("Failed to accept ride: " . $e->getMessage());
    //         return response()->json(['error' => 'Failed to accept ride. Please try again.'], 500);
    //     }
    // }


    private function updateRideHistory($ride_id, $rider_id)
    {

        $ride = RideHistory::where('ride_id', $ride_id)
                                ->where('status', 'Available')
                                ->lockForUpdate()
                                ->first();
        if ($ride) {

            $apply = RideApplication::where('ride_id', $ride_id)
                                ->where('status', 'Matched')
                                ->where('apply_to', $rider_id);

            if($apply){
                // broadcast(new RideApply($apply));
                $rides = $this->ridesService->getAvailableRides();
                event(new RidesUpdated($rides));

                
            }


            $ride->rider_id = $rider_id ;
            $ride->status = "Booked";
            $ride->save();

            $data = $this->dashboardService->getCounts();
                $counts = $data['counts'];
                $bookings = $data['bookings'];
                
                event(new DashboardUpdated($counts, $bookings));


            
            Log::info("Ride history updated successfully for ride ID: " . $ride_id);
        } else {
            Log::warning("Ride history not found for ride ID: " . $ride_id);
        }
    }


    public function apply_rider(Request $request, $ride_id)
    {
        Log::info("Attempting to apply ride with ID: " . $ride_id);

        try {
            return DB::transaction(function () use ($ride_id, $request) {
                $validated = $request->validate([
                    'user_id' => 'required|exists:users,user_id',
                    'rider_id' => 'required',
                ]);

                $user_id = $validated['user_id'];
                $rider_id = $validated['rider_id'];

                $ride = RideHistory::where('ride_id', $ride_id)
                                ->where('status', 'Available')
                                ->lockForUpdate()
                                ->first();

                if (!$ride) {
                    Log::warning("Ride not available for acceptance: " . $ride_id);
                    return response()->json(['message' => 'This ride is no longer available.'], 200);
                }

                $check = RideApplication::where('ride_id', $ride_id)
                                ->where('applier', $user_id)
                                ->where('apply_to', $rider_id)
                                ->where('status', 'Pending')
                                ->lockForUpdate()
                                ->first();

                if ($check) {
                    Log::warning("You have already applied for this ride: " . $ride_id);
                    return response()->json(['message' => 'exist'], 200);
                }

                $accept = RideApplication::where('ride_id', $ride_id)
                                ->where('apply_to', $user_id)
                                ->lockForUpdate()
                                ->first();

                if ($accept) {
                    $accept->status = 'Matched';
                    $accept->save();

                    $this->updateRideHistory($ride_id, $rider_id);

                    Log::info("Ride Matched successfully: " . $ride_id);
                    return response()->json(['message' => 'Accepted Successfully.']);
                }

                $apply = new RideApplication();
                $apply->ride_id = $ride_id;
                $apply->applier = $user_id;
                $apply->status = 'Pending';
                $apply->apply_to = $rider_id;
                $apply->date = now();
                $apply->save();

                $user = User::where('user_id', $user_id)
                    ->first(['first_name', 'last_name', 'mobile_number', 'status']);
                Log::info("User: " . $user->first_name);

                // Fetch complete ride data including locations
                $completeRideData = RideHistory::join('users', 'ride_histories.user_id', '=', 'users.user_id')
                    ->join('ride_locations', 'ride_histories.ride_id', '=', 'ride_locations.ride_id')
                    ->where('ride_histories.ride_id', $ride_id)
                    ->select(
                        'ride_histories.*',
                        'users.first_name',
                        'users.last_name',
                        'ride_locations.customer_latitude',
                        'ride_locations.customer_longitude',
                        'ride_locations.dropoff_latitude',
                        'ride_locations.dropoff_longitude'
                    )
                    ->first();

                // Create payload with matching structure
                $applicationData = collect([
                    array_merge($completeRideData->toArray(), [
                        'apply_id' => $apply->apply_id,
                        'applier' => $apply->applier,
                        'apply_to' => $apply->apply_to,
                        'applier_name' => $user->first_name . ' ' . $user->last_name,
                    ])
                ]);

                // Log the complete payload
                Log::info("Broadcasting Ride Data: " . json_encode($applicationData));

                // Broadcast the event
                event(new RideApply($applicationData));

                Log::info("Ride Application successfully: " . $ride_id);
                return response()->json(['message' => 'Applied Successfully.']);
            });
        } catch (\Exception $e) {
            Log::error("Failed to apply ride: " . $e->getMessage());
            Log::error("Exception stack trace: " . $e->getTraceAsString()); // Add this for more detailed error info
            return response()->json(['error' => 'Failed to apply ride. Please try again.'], 500);
        }
    }
    

    


    public function getRiderLocations()
    {
        $riders = Rider::where('availability', 'Available')
            ->with(['user'])
            ->get();

        return response()->json($riders);
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
        $rides = $this->ridesService->getAvailableRides();
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