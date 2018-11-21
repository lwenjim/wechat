<?php

namespace App\Models;

class UserMsg extends Model
{
    protected $table = 'user_msg';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
