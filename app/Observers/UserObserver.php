<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Rider;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if ($user->role_id === User::ROLE_RIDER) {
            Rider::create([
                'user_id' => $user->user_id,
                'registration_date' => $user->created_at,
                'verification_status' => 'Unverified', // or whatever default status you want
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // If a user's role is changed to RIDER, create a Rider record
        if ($user->isDirty('role_id') && $user->role_id === User::ROLE_RIDER) {
            Rider::firstOrCreate(
                ['user_id' => $user->user_id],
                [
                    'registration_date' => $user->created_at,
                    'verification_status' => 'pending',
                ]
            );
        }
    }
}