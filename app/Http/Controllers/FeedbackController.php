<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = Feedback::select(
            'feedbacks.*',
            'sender_user.first_name as sender_first_name',
            'sender_user.last_name as sender_last_name',
            'recipient_user.first_name as recipient_first_name',
            'recipient_user.last_name as recipient_last_name'
        )
        ->leftJoin('users as sender_user', 'sender_user.user_id', '=', 'feedbacks.sender_id')
        ->leftJoin('users as recipient_user', 'recipient_user.user_id', '=', 'feedbacks.recipient_id')
        ->get();

        return response()->json($feedbacks);
    }
}