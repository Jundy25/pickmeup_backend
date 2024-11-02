<?php

namespace App\Http\Controllers;

use App\Events\DashboardUpdated;
use App\Models\User;
use App\Models\Rider;
use App\Models\RideHistory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getCounts()
    {
        $activeRidersCount = User::where('role_id', User::ROLE_RIDER)
            ->where('status', 'Active')
            ->count();

        $disabledRidersCount = User::where('role_id', User::ROLE_RIDER)
            ->where('status', 'Disabled')
            ->count();

        $admincount = User::where('role_id', User::ROLE_ADMIN)
            ->where('status', 'Active')
            ->count();
    
        $customersCount = User::where('role_id', User::ROLE_CUSTOMER)->count();
        
        $completedRidesCount = RideHistory::where('status', 'Completed')->count();

        $verified = Rider::where('verification_status', 'Verified')
            ->count();

        $pending = Rider::where('verification_status', 'Pending')
            ->count();

        $counts = [
            'active_riders' => $activeRidersCount,
            'disabled_riders' => $disabledRidersCount,
            'admincount' => $admincount,
            'customers' => $customersCount,
            'completed_rides' => $completedRidesCount,
            'verified' => $verified,
            'pending' => $pending
        ];

        // Fetch RideHistory records, ordered by latest to oldest
        $bookings = RideHistory::with(['user', 'rider'])
            ->orderBy('created_at', 'desc')
            ->limit(5) // Adjust the limit as needed
            ->get();

        return response()->json([
            'counts' => $counts,
            'bookings' => $bookings,
        ]);
    }
}