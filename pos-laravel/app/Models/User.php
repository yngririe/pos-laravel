<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // The legacy users table only has created_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'full_name', 'branch_name', 'username', 'email', 'phone',
        'status', 'avatar', 'password', 'role', 'branch_id',
    ];

    protected $hidden = ['password'];

    // Existing bcrypt hashes ($2y$...) keep working; new passwords are hashed on assignment.
    protected $casts = ['password' => 'hashed'];
}
