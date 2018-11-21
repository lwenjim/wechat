<?php

namespace App\Models;

class WeChatAchieve extends Model
{
    protected $table = 'wechat_achieve';
    protected $casts = [
        'content' => 'array'
    ];

    public function wechat()
    {
        return $this->belongsTo(Wechat::class);
    }
}
