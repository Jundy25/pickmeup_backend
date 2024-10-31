<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rider;
use App\Models\RideHistory;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function getAdmin()
    {
        $admin = User::where('role_id', User::ROLE_ADMIN)->get(['user_id', 'first_name', 'last_name', 'email', 'mobile_number', 'date_of_birth', 'user_name','gender', 'status']);
        return response()->json($admin);
    }


    public function updateStatus(Request $request, $user_id)
    {
        $request->validate([
            'status' => 'required|in:Active,Disabled',
        ]);

        $user = User::findOrFail($user_id); // Ensure the user exists
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user
        ]);
    }

    public function show($user_id)
    {
        $admin = User::where('user_id', $user_id)->where('role_id', 2)->first();
        if ($admin) {
            return response()->json($admin);
        }
        return response()->json(['message' => 'Admin not found'], 404);
    }


    public function updateAdmin(Request $request, $id)
    {
        // Retrieve the current admin data to compare with the new input
        $admin = User::findOrFail($id);

        // Define base validation rules
        $rules = [
            'user_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:Male,Female',
            'date_of_birth' => 'required|date',
            'email' => 'required|email|max:255',
            'mobile_number' => 'required|string|max:15',
        ];

        // Apply unique validation only if the username or email has changed
        if ($request->input('user_name') !== $admin->user_name) {
            $rules['user_name'] = 'required|string|max:255|unique:users,user_name';
        }
        
        if ($request->input('email') !== $admin->email) {
            $rules['email'] = 'required|email|max:255|unique:users,email';
        }

        // Define custom messages
        $messages = [
            'user_name.unique' => 'The username is already taken.',
            'email.unique' => 'The email is already in use.',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Update the admin user data
            $admin->user_name = $request->input('user_name');
            $admin->first_name = $request->input('first_name');
            $admin->last_name = $request->input('last_name');
            $admin->gender = $request->input('gender');
            $admin->date_of_birth = $request->input('date_of_birth');
            $admin->email = $request->input('email');
            $admin->mobile_number = $request->input('mobile_number');

            // Save the updated data
            $admin->save();

            return response(['message' => 'Admin updated successfully', 'admin' => $admin], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating admin: ' . $e->getMessage());
            return response(['message' => 'Error updating admin', 'error' => $e->getMessage()], 500);
        }
    }


    public function accountUpdate(UpdateUserRequest $request, User $editingAdminId)
    {
        try {
            $userDetails = User::find($editingAdminId);

            if (!$userDetails) {
                return response(["message" => "User not found"], 404);
            }

            // Exclude null or empty password from the update
            $data = $request->validated();
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $userDetails->update($data);

            return response(["message" => "User Successfully Updated"], 200);
        } catch (\Throwable $th) {
            return response(["message" => $th->getMessage()], 400);
        }
    }

    public function verify_rider(Request $request, $user_id)
    {
        $request->validate([
            'status' => 'required|in:Verified,Pending',
        ]);

        $rider = Rider::where('user_id', $user_id)->firstOrFail();
        $rider->verification_status = $request->status;
        $rider->save();

        return response()->json([
            'message' => 'Rider verification status updated successfully',
            'rider' => $rider
        ]);
    }

}