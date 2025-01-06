<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'quantity',
        'unit_price',
        'unit_type',
        'reorder_level',
        'description',
        'supplier',
    ];

    public function isLowStock()
    {
        return $this->quantity <= $this->reorder_level;
    }

    public function updateStock($quantity, $type = 'add')
    {
        if ($type === 'add') {
            $this->quantity += $quantity;
        } else {
            $this->quantity -= $quantity;
        }
        $this->save();
    }
}
