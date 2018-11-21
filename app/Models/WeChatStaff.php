<?php

namespace App\Models;

class WeChatStaff extends Model
{
    protected $table = 'wechat_staff';

    public function wechat()
    {
        return $this->belongsTo(Wechat::class);
    }
}
