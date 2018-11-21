<?php

namespace App\Models;

class WechatUser extends Model
{
    protected $table = 'wechat_user';

    public function wechat()
    {
        return $this->belongsTo(Wechat::class);
    }
}
