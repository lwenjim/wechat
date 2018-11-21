<?php

namespace App\Models;

class WeChatReply extends Model
{
    protected $table = 'wechat_reply';

    public function wechat()
    {
        return $this->belongsTo(Wechat::class);
    }
}
