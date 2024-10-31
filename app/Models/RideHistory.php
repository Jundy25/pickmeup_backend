<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideHistory extends Model
{
    use HasFactory;

    protected $primaryKey = 'ride_id';

    protected $fillable = [
        'user_id',
        'rider_id',
        'ride_date',
        'pickup_location',
        'dropoff_location',
        'fare',
        'status',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id', 'user_id');
    }
    
    public function rideLocations()
    {
        return $this->hasOne(RideLocation::class, 'ride_id', 'ride_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'ride_id', 'ride_id');
    }
}
