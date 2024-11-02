<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideApplication extends Model
{
    use HasFactory;

    protected $table = 'ride_applications';

    protected $primaryKey = 'apply_id';

    protected $fillable = [
        'ride_id',
        'applier',
        'date',
        'apply_to',
        'status',
    ];

    /**
     * Define a relationship to the RideHistory model.
     */
    public function rideHistory()
    {
        return $this->belongsTo(RideHistory::class, 'ride_id');
    }
}
