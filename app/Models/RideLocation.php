<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideLocation extends Model
{
    use HasFactory;

    protected $table = 'ride_locations';

    protected $fillable = [
        'rider_latitude',
        'rider_longitude',
        'customer_latitude',
        'customer_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
    ];

    // Relationship to User

    public function rideHistory()
    {
        return $this->belongsTo(RideHistory::class, 'ride_id', 'ride_id');
    }
}
