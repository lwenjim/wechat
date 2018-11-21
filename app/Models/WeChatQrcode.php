<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class WeChatQrcode extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['users'];
    protected $table = 'wechat_qrcode';

    public function wechat()
    {
        return $this->belongsTo(Wechat::class);
    }

    public function users()
    {
        return $this->hasMany(WeChatQrcodeUser::class, 'wechat_qrcode_id');
    }
}
