<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'price', 'discounted_price', 'category', 'image', 'is_active'];
}
