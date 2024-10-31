<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Confirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'mobile_number',
        'otp',
        'status',
    ];

    public $timestamps = true;  // Ensure timestamps are included if necessary
}
