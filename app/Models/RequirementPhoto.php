<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequirementPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_id',
        'requirement_id',
        'photo_url',
    ];

    public function rider()
    {
        return $this->belongsTo(Rider::class, 'rider_id', 'rider_id');
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }
}
