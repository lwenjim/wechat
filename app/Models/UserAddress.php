<?php

namespace App\Models;

class UserAddress extends Model
{
    protected $table = 'user_address';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
