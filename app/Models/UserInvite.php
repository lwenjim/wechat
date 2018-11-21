<?php

namespace App\Models;

class UserInvite extends Model
{
    protected $table = 'user_invite';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invite_user()
    {
        return $this->belongsTo(User::class, 'invite_user_id');
    }
}
