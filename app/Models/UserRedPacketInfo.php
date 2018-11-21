<?php

namespace App\Models;

class UserRedPacketInfo extends Model
{
    protected $table = 'user_red_packet_info';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wechat()
    {
        return $this->belongsTo(Wechat::class, 'appid', 'appid');
    }
}
