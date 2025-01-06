<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'birthdate',
        'loyalty_points',
        'preferences',
        'allergies'
    ];

    protected $casts = [
        'birthdate' => 'date',
        'loyalty_points' => 'integer',
        'preferences' => 'array',
        'allergies' => 'array'
    ];

    public function addLoyaltyPoints($points)
    {
        $this->loyalty_points += $points;
        $this->save();
    }

    public function getFormattedPhoneAttribute()
    {
        // Format phone number for display
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $this->phone);
    }
}
