<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'name',
        'description',
        'ingredients',
        'instructions',
        'preparation_time',
        'cooking_time',
        'serving_size',
        'cost_per_serving',
        'category',
        'image'
    ];

    protected $casts = [
        'ingredients' => 'array',
        'instructions' => 'array',
        'preparation_time' => 'integer',
        'cooking_time' => 'integer',
        'serving_size' => 'integer',
        'cost_per_serving' => 'decimal:2'
    ];

    public function getTotalTimeAttribute()
    {
        return $this->preparation_time + $this->cooking_time;
    }
}
