<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Specify the table name if it doesn’t follow Laravel’s naming convention
    protected $table = 'payments';

    // Define the primary key if it's not "id"
    protected $primaryKey = 'payment_id';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'user_id',
        'amount',
        'date'
    ];

    /**
     * Define the relationship to the User model
     * Assuming each payment belongs to one user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
