<?php

namespace App\Models;

class UserPhone extends Model
{
    protected $table = 'user_phone';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
