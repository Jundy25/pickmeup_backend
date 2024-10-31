<?php

namespace App\Http\Controllers;

use App\Models\RideHistory;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index()
    {
        $rideHistories = RideHistory::with(['user', 'rider'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($rideHistories);
    }

    public function customerHistory($user_id)
    {
        $rideHistories = RideHistory::where('user_id', $user_id)
            ->with(['user'])
            ->get();
        return response()->json($rideHistories);
    }
    
    public function riderHistory($user_id)
    {
        $rideHistories = RideHistory::where('rider_id', $user_id)
            ->with(['user'])
            ->get();
        return response()->json($rideHistories);
    }
}
