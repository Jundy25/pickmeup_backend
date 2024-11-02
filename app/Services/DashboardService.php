<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rider;
use App\Models\RideHistory;

class DashboardService
{
    public function getCounts()
    {
        $activeRidersCount = User::where('role_id', User::ROLE_RIDER)->where('status', 'Active')->count();
        $disabledRidersCount = User::where('role_id', User::ROLE_RIDER)->where('status', 'Disabled')->count();
        $adminCount = User::where('role_id', User::ROLE_ADMIN)->where('status', 'Active')->count();
        $customersCount = User::where('role_id', User::ROLE_CUSTOMER)->count();
        $completedRidesCount = RideHistory::where('status', 'Completed')->count();
        $verified = Rider::where('verification_status', 'Verified')->count();
        $pending = Rider::where('verification_status', 'Pending')->count();

        $counts = [
            'active_riders' => $activeRidersCount,
            'disabled_riders' => $disabledRidersCount,
            'admin_count' => $adminCount,
            'customers' => $customersCount,
            'completed_rides' => $completedRidesCount,
            'verified' => $verified,
            'pending' => $pending,
        ];

        $bookings = RideHistory::with(['user', 'rider'])->orderBy('created_at', 'desc')->limit(5)->get();

        return compact('counts', 'bookings');
    }
}
