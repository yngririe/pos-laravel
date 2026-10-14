<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'price', 'discounted_price', 'category', 'image'];
}
