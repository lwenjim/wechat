<?php

namespace App\Models;

class UserWalkGive extends Model
{
    public $timestamps = false;
    protected $table = 'user_walk_give';

    public function user_walk()
    {
        return $this->belongsTo(UserWalk::class, 'to_user_walk_id');
    }

    public function to_user_walk()
    {
        return $this->belongsTo(UserWalk::class, 'user_walk_id');
    }
}
