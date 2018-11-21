<?php

namespace App\Models;

class UserCoin extends Model
{
    protected $table = 'user_coin';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'action_id');
    }
}
