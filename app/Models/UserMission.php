<?php

namespace App\Models;

class UserMission extends Model
{
    protected $table = 'user_mission';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
