<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'email',
        'user_name',
        'password',
        'mobile_number',
        'status'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role_id' => 'integer',
        ];
    }

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public const ROLE_SUPERADMIN = 1;
    public const ROLE_ADMIN = 2;
    public const ROLE_RIDER = 3;
    public const ROLE_CUSTOMER = 4;

    public function rider()
    {
        return $this->hasOne(Rider::class, 'user_id', 'user_id');
    }

    public function rideHistories()
    {
        return $this->hasMany(RideHistory::class, 'user_id', 'user_id');
    }

    public function ridesAsRider()
    {
        return $this->hasMany(RideHistory::class, 'rider_id', 'user_id');
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'sender');
    }

    public function receivedFeedbacks()
    {
        return $this->morphMany(Feedback::class, 'recipient');
    }

   
}
