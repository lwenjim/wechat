<?php

namespace App\Models;

class UserLike extends Model
{
    protected $table = 'user_like';
    public $timestamps = false;

    public function item()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
