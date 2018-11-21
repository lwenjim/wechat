<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

use App\Models\WechatUser;
use App\Models\TokenUsers;

class Wechat extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['qrcodes', 'replies', 'staffs'];
    protected $table = 'wechat';
    protected $casts = [
        'portrait' => 'array',
        'statistics' => 'array',
        'region' => 'array',
        'summary_phone_model' => 'array',
    ];

    public function qrcodes()
    {
        return $this->hasMany(WeChatQrcode::class);
    }

    public function replies()
    {
        return $this->hasMany(WeChatReply::class);
    }

    public function reply()
    {
        return $this->hasMany(Reply::class);
    }

    public function staffs()
    {
        return $this->hasMany(WeChatStaff::class);
    }

    public function achieves()
    {
        return $this->hasMany(WeChatAchieve::class);
    }

    public function usersSimple()
    {
        return $this->belongsToMany(User::class, 'wechat_user', 'wechat_id', 'user_id');
    }

    public function users()
    {
        return $this->usersSimple()->withPivot('openid', 'subscribe', 'subscribe_time', 'is_default');
    }

    public function user($key, $val)
    {
        return $this->users()->wherePivot($key, $val);
    }

    public function validUser()
    {
        return $this->users()->wherePivot('subscribe', 1)->where('mini_user_id', '>', 0);
    }

    public function fans()
    {
        return $this->hasMany(WechatUser::class, 'wechat_id', 'id');
    }

    public function achievement()
    {
        return $this->hasMany(Achievement::class, 'appid', 'appid');
    }

    public function user_sign()
    {
        return $this->hasMany(UserSign::class, 'wechat_id', 'id');
    }
}
