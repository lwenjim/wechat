<?php

namespace App\Models;

use App\Services\ModelCache\ModelCache;

class UserWalk extends Model
{
    use ModelCache;
    protected $cacheTag = 'user_walk';
    protected $table = 'user_walk';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gives()
    {
        return $this->hasMany(UserWalkGive::class, 'user_walk_id');
    }

    public function to_gives()
    {
        return $this->hasMany(UserWalkGive::class, 'to_user_walk_id');
    }
}
