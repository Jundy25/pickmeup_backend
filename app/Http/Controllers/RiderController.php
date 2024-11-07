<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rider;
use App\Models\RequirementPhoto;
use App\Models\RideApplication;
use App\Models\Requirement;
use App\Models\RideHistory;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Events\RidesUpdated;
use App\Events\DashboardUpdated;
use App\Events\RideBooked;

use App\Services\DashboardService;
use App\Services\RidesService;

class RiderController extends Controller
{
    
    protected $ridesService;

    public function __construct(
        DashboardService $dashboardService, 
        RidesService $ridesService)
        {
            $this->dashboardService = $dashboardService;
            $this->ridesService = $ridesService;
        }

    public function getRiders()
    {
        $riders = User::where('role_id', User::ROLE_RIDER)
            ->whereHas('rider', function($query) {
                $query->where('verification_status', 'Verified');
            })
            ->with(['rider:verification_status,user_id,rider_id', 'rider.requirementPhotos' => function($query) {
                $query->whereIn('requirement_id', [3, 6]) // Assuming 1 is for license and 2 is for OR
                    ->select('rider_id', 'requirement_id', 'photo_url');
            }])
            ->get(['user_id', 'first_name', 'last_name', 'mobile_number', 'status', 'email', 'date_of_birth']);

        return response()->json($riders);
    }

    public function updateAvailability(Request $request)
    {
        $rider = Rider::where('user_id', $request->input('user_id'))->first();
    
        if ($rider) {
            $rider->rider_latitude = $request->input('latitude');
            $rider->rider_longitude = $request->input('longitude');
            $rider->availability = $request->input('status');
            
            $rider->save();
    
            return response()->json($rider);
        } else {
            return response()->json(['error' => 'Rider not found'], 404);
        }
    }

    public function getRiderById($user_id)
    {
        $user = User::where('role_id', User::ROLE_RIDER)
            ->where('user_id', $user_id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        $rider = Rider::where('user_id', $user_id)->first();

        if (!$rider) {
            return response()->json(['message' => 'Rider not found'], 404);
        }

        if (in_array($rider->verification_status, ['Unverified', 'Pending'])) {
            return response()->json(['message' => 'Get Verified'], 200);
        }

        if ($user->status === 'Disabled') {
            return response()->json(['message' => 'Account Disabled'], 200);
        }

        return response()->json($rider, 200);
    }

    public function getApplications($userId)
    {
        // Retrieve the oldest Ride Application for the specified user
        $apply = RideApplication::where('apply_to', $userId)
            ->where('status', 'Pending')
            ->with('ridehistory')
            ->oldest()
            ->first();

        if (!$apply) {
            Log::info("No applications found for user_id: " . $userId);
            return response()->json(['message' => 'No applications found', 'data' => []], 200);
        }

        // Fetch user details
        $user = User::where('user_id', $apply->applier)
                    ->first(['first_name', 'last_name', 'mobile_number', 'status']);
        
        if (!$user) {
            Log::info("User not found for applier_id: " . $apply->applier);
            return response()->json(['message' => 'User not found', 'data' => []], 200);
        }

        // Fetch complete ride data
        $completeRideData = RideHistory::join('users', 'ride_histories.user_id', '=', 'users.user_id')
                        ->join('ride_locations', 'ride_histories.ride_id', '=', 'ride_locations.ride_id')
                        ->where('ride_histories.ride_id', $apply->ride_id)
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

        if (!$completeRideData) {
            Log::info("Ride data not found for ride_id: " . $apply->ride_id);
            return response()->json(['message' => 'Ride data not found', 'data' => []], 200);
        }

        // Create payload with matching structure
        $applicationData = array_merge($completeRideData->toArray(), [
            'apply_id' => $apply->apply_id,
            'applier' => $apply->applier,
            'apply_to' => $apply->apply_to,
            'applier_name' => $user->first_name . ' ' . $user->last_name,
        ]);

        return response()->json(['message' => 'Application retrieved successfully', 'data' => [$applicationData]], 200);
    }


    

    public function getAvailableRides()
    {
        $availableRides = $this->ridesService->getAvailableRides();
    
        return response()->json($availableRides);
    }

    public function getRidersRequirements()
    {
        // Fetch riders with their related user data and requirement photos
        $riders = Rider::with([
            'user', // Load the associated User model
            'requirementphotos' // Load the associated RequirementPhoto model
        ])->get();

        // Return the data as JSON
        return response()->json($riders);
    }



    public function upload(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'requirement_id' => 'required|integer',
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'user_id' => 'required|integer',
            ]);
    
            $rider = Rider::where('user_id', $request->input('user_id'))->first();
    
            // Check if the rider exists
            if (!$rider) {
                return response()->json(['success' => false, 'message' => 'Rider not found.'], 404);
            }
    
            // Handle file upload
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('verification_documents', 'public');
    
                // Check for existing photo record
                $requirementPhoto = RequirementPhoto::where('requirement_id', $request->input('requirement_id'))
                                                     ->where('rider_id', $rider->rider_id)
                                                     ->first();
    
                if ($requirementPhoto) {
                    // Delete the existing image file
                    if ($requirementPhoto->photo_url) {
                        Storage::disk('public')->delete($requirementPhoto->photo_url);
                    }
    
                    // Update existing record
                    $requirementPhoto->photo_url = $photoPath;
                    $requirementPhoto->save();
                } else {
                    // Create a new record
                    RequirementPhoto::create([
                        'requirement_id' => $request->input('requirement_id'),
                        'photo_url' => $photoPath,
                        'rider_id' => $rider->rider_id,
                    ]);
                }
            }
    
            return response()->json(['success' => true, 'message' => 'File uploaded successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'File upload failed.', 'error' => $e->getMessage()]);
        }
    }
    

    public function updateRiderInfo(Request $request)
    {
        try {
            // Validate text data
            $request->validate([
                'drivers_license_number' => 'required|string',
                'license_expiration_date' => 'required|date',
                'or_expiration_date' => 'required|date',
                'plate_number' => 'required|string',
                'user_id' => 'required|integer', // Ensure user_id is included in the request
            ]);

            \Log::info("Updating rider info for user_id: " . $request->input('user_id'));

            // Retrieve the rider based on user_id
            $rider = Rider::where('user_id', $request->input('user_id'))->first();

            if (!$rider) {
                return response()->json(['success' => false, 'message' => 'Rider not found.']);
            }

            // Prepare data to update or create in requirement_photos table
            $requirementsData = [
                5 => $request->input('drivers_license_number'), // For requirement_id 1
                6 => $request->input('license_expiration_date'), // For requirement_id 2
                3 => $request->input('or_expiration_date'), // For requirement_id 4
                10 => $request->input('plate_number'), // For requirement_id 5
            ];

            foreach ($requirementsData as $requirement_id => $value) {
                // Check if the requirement photo exists
                $requirementPhoto = RequirementPhoto::where('rider_id', $rider->rider_id)
                    ->where('requirement_id', $requirement_id)
                    ->first();

                if ($requirementPhoto) {
                    // Update the existing record
                    $requirementPhoto->update(['text_data' => $value]); // Replace 'text_data' with the actual column name you want to update
                } else {
                    // Create a new record if it doesn't exist
                    RequirementPhoto::create([
                        'rider_id' => $rider->rider_id,
                        'requirement_id' => $requirement_id,
                        'photo_url' => $value, // Replace 'text_data' with the actual column name
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Rider information updated successfully.']);
        } catch (\Exception $e) {
            \Log::error("Error updating rider info: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update rider information.', 'error' => $e->getMessage()]);
        }
    }


    public function getUploadedImages($userId)
    {
        try {
            $rider = Rider::where('user_id', $userId)->first();
            // Fetch the photos for the given rider ID
            $photos = RequirementPhoto::where('rider_id', $rider->rider_id)->get();

            // Prepare the response data
            $response = [
                'license_image_url' => null,
                'or_cr_image_url' => null,
                'cor_image_url' => null,
                'motor_model_image_url' => null,
                'tpl_insurance_image_url' => null,
                'brgy_clearance_image_url' => null,
                'police_clearance_image_url' => null,
            ];

            // Map the photos to their respective URLs based on the requirement_id
            foreach ($photos as $photo) {
                switch ($photo->requirement_id) {
                    case 1:
                        $response['motor_model_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    case 2:
                        $response['or_cr_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    case 4:
                        $response['cor_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    case 5:
                        $response['license_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    case 8:
                        $response['tpl_insurance_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    case 9:
                        $response['brgy_clearance_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    case 10:
                        $response['police_clearance_image_url'] = asset('storage/' . $photo->photo_url);
                        break;
                    default:
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $response,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching images: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status = $request->input('status');
            $user->save();

            return response()->json(['message' => 'User status updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update user status.'], 500);
        }
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

    //             Log::info("Ride accepted successfully: " . $ride_id);
    //             return response()->json(['message' => 'Ride Accepted Successfully.']);
    //         });
    //     } catch (\Exception $e) {
    //         Log::error("Failed to accept ride: " . $e->getMessage());
    //         return response()->json(['error' => 'Failed to accept ride. Please try again.'], 500);
    //     }
    // }


    private function updateRideHistory($ride_id, $customer, $user_id)
    {
        $ride = RideHistory::where('ride_id', $ride_id)
                            ->where('status', 'Available')
                            ->lockForUpdate()
                            ->first();
    
        if ($ride) {
            $apply = RideApplication::where('ride_id', $ride_id)
                                    ->where('status', 'Matched')
                                    ->where('apply_to', $customer)
                                    ->first();  // Make sure this returns a result, otherwise handle empty state
    
            if ($apply) {
                // Broadcasting and event handling code goes here...
    
                $completeRideData = RideHistory::join('users', 'ride_histories.user_id', '=', 'users.user_id')
                                                ->join('ride_locations', 'ride_histories.ride_id', '=', 'ride_locations.ride_id')
                                                ->where('ride_histories.ride_id', $ride_id)
                                                ->select(
                                                    'ride_histories.*',
                                                    'users.first_name',
                                                    'users.last_name',
                                                )
                                                ->first();
    
                $customer_name = User::where('user_id', $customer)
                                     ->first(['first_name', 'last_name', 'mobile_number', 'status']);
    
                Log::info("User: " . $customer_name->first_name);
    
                // Merge data without collecting it
                $ride = array_merge($completeRideData->toArray(), [
                    'apply_id' => $apply->apply_id,
                    'applier' => $apply->applier,
                    'apply_to' => $apply->apply_to,
                    'rider_name' => $customer_name->first_name . ' ' . $customer_name->last_name,
                ]);
    
                // Event broadcasting
                event(new RideBooked($ride));
            }
    
            // Update the ride history record
            $ride->rider_id = $user_id;
            $ride->status = "Booked";
            $ride->save(); // Save directly to the RideHistory model
    
            // Fetch dashboard counts and bookings
            $data = $this->dashboardService->getCounts();
            $counts = $data['counts'];
            $bookings = $data['bookings'];
            
            // Broadcast the dashboard update
            event(new DashboardUpdated($counts, $bookings));
    
            Log::info("Ride history updated successfully for ride ID: " . $ride_id);
        } else {
            Log::warning("Ride history not found for ride ID: " . $ride_id);
        }
    }
    

    public function apply_ride(Request $request, $ride_id)
    {
        Log::info("Attempting to apply ride with ID: " . $ride_id);

        try {
            return DB::transaction(function () use ($ride_id, $request) {
                $validated = $request->validate([
                    'user_id' => 'required|exists:users,user_id',
                    'customer_id' => 'required',
                ]);

                $user_id = $validated['user_id'];
                $customer = $validated['customer_id'];

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
                                ->where('apply_to', $customer)
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

                    $this->updateRideHistory($ride_id, $customer, $user_id);

                    Log::info("Ride Matched successfully: " . $ride_id);
                    return response()->json(['message' => 'accept']);
                }

                $apply = new RideApplication();
                $apply->ride_id = $ride_id;
                $apply->applier = $user_id;
                $apply->status = 'Pending';
                $apply->apply_to = $ride->user_id;
                $apply->date = now();
                $apply->save();

                Log::info("Ride Application successfully: " . $ride_id);
                return response()->json(['message' => 'apply.']);
            });
        } catch (\Exception $e) {
            Log::error("Failed to accept ride: " . $e->getMessage());
            return response()->json(['error' => 'Failed to accept ride. Please try again.'], 500);
        }
    }


    public function checkActiveRide($user_id)
    {
        $activeRide = RideHistory::where('rider_id', $user_id)
            ->whereIn('status', ['Booked', 'In Transit'])
            ->with(['user', 'ridelocations'])
            ->latest()
            ->first();

        return response()->json([
            'hasActiveRide' => $activeRide !== null,
            'rideDetails' => $activeRide
        ]);
    }

    public function start_ride(Request $request, $ride_id)
    {
        $ride = RideHistory::find($ride_id);
    
        if (!$ride || $ride->status == 'Canceled') {
            return response()->json(['error' => 'This ride is no longer available.'], 400);
        }
    
        // Logic to cancel the ride
        $ride->status = 'In Transit';
        $ride->save();
    
        return response()->json(['message' => 'Now In Transit']);
    }

    public function finish_ride(Request $request, $ride_id)
    {
        $ride = RideHistory::find($ride_id);
    
        if (!$ride || $ride->status == 'Canceled') {
            return response()->json(['error' => 'This ride is no longer available.'], 400);
        }
    
        // Logic to cancel the ride
        $ride->status = 'Review';
        $ride->save();
    
        return response()->json(['message' => 'Ride successfully ended']);
    }


}