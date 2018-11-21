<?php

namespace App\Models;

class KefuConnMsg extends Model
{
    protected $table = 'kefu_conn_msg';

    public function kefu_conn()
    {
        return $this->belongsTo(KefuConn::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
