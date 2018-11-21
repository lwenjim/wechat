<?php

namespace App\Models;


class PlatformApply extends Model
{
    protected $table = 'platform_apply';

    function wechat()
    {
        return $this->hasOne(Wechat::class, 'appid', 'appid');
    }

    function tokenuser()
    {
        return $this->hasOne(TokenUsers::class, 'id', 'user_id');
    }

    function wechatUserSubscribeLogs()
    {
        return $this->hasMany(WechatUserSubscribeLogs::class, 'appid', 'appid');
    }
}
