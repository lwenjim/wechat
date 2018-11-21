<?php

namespace App\Models;

class UserSignYear extends Model
{
    protected $table = 'user_sign_year';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
