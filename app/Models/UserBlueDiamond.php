<?php

namespace App\Models;

use App\Services\ModelCache\ModelCache;
class UserBlueDiamond extends Model
{
    use ModelCache;
    protected $table = 'user_blue_diamond';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function to_likes()
    {
        return $this->hasMany(UserLike::class, 'to_user_id','user_id');
    }
}
