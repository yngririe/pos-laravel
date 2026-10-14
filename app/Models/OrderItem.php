<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'menu_item_id', 'name', 'price', 'quantity'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
