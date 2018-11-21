<?php

namespace App\Models;

class WeChatQrcodeUser extends Model
{
    protected $table = 'wechat_qrcode_user';
    public $timestamps = false;

    public function qrcode()
    {
        return $this->belongsTo(WeChatQrcode::class, 'wechat_qrcode_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
