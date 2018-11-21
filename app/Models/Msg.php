<?php

namespace App\Models;

class Msg extends Model
{
    protected $table = 'msg';

    public function session()
    {
        return $this->belongsTo(MsgSession::class,'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
