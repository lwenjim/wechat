<?php

namespace App\Models;


class TokenUsers extends Model
{
    protected $table = 'token_users';

    protected $fillable = [
        'username', 'password', 'email', 'api_token', 'phone', 'appid','invitecode','invitedcode','openid'
    ];

    protected $hidden = [
        'password',
    ];

    public function batchSendLog($wechat_id = 0)
    {
        $builder = $this->hasMany(UserBatchSendLog::class);
        if ($wechat_id > 0) {
            $builder = $builder->where('wechat_id', $wechat_id);
        }
        return $builder;
    }
}
