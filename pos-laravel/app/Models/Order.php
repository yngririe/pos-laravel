<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    const UPDATED_AT = null;

    // created_at and served_at are stored in UTC. They are left as plain strings
    // on purpose: served_at is written explicitly and converted for display.
    protected $fillable = [
        'branch_id', 'beeper_number', 'order_type', 'payment_method', 'amount_paid',
        'subtotal', 'discount', 'total', 'change_amount',
        'gcash_reference', 'gcash_reference_extra', 'gcash_extra_amount',
        'refund_amount', 'status', 'served_at',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
