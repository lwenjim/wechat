<?php

namespace App\Models;

class MsgUser extends Model
{
    protected $table = 'msg_user';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
