<?php

namespace App\Models;

class UserFeedback extends Model
{
    protected $table = 'user_feedback';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
