<?php

namespace App\Models;

class UserSignAdd extends Model
{
    protected $table = 'user_sign_add';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
