<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    // Specify the table name if it doesn’t follow Laravel’s naming convention
    protected $table = 'delivery';

    // Define the primary key if it's not "id"
    protected $primaryKey = 'delivery_id';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'ride_id',
        'ride_date',
        'pickup_location',
        'dropoff_location',
        'description',
        'fare',
        'status',
    ];

    /**
     * Define the relationship to the RideHistory model
     * Assuming each delivery is associated with a ride history record
     */
    public function rideHistory()
    {
        return $this->belongsTo(RideHistory::class, 'ride_id', 'ride_id');
    }
}
