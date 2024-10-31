<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    use HasFactory;

    protected $primaryKey = 'rider_id';

    protected $fillable = [
        'user_id',
        'registration_date',
        'verification_status',
    ];

    protected static function booted()
    {
        static::creating(function ($rider) {
            if (!$rider->registration_date) {
                $rider->registration_date = $rider->user->created_at;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function requirementPhotos()
    {
        return $this->hasMany(RequirementPhoto::class, 'rider_id', 'rider_id');
    }

    public function rideHistories()
    {
        return $this->hasMany(RideHistory::class, 'rider_id', 'rider_id');
    }

}
