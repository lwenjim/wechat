<?php

namespace App\Models;

class Achievement extends Model
{
    protected $table = 'achievement';
    protected $casts = [
        'content' => 'array'
    ];

    public function tips()
    {
        return $this->hasOne('App\Models\AchievementTips', 'id', 'tips')->where('is_del', 0)->select('id', 'name');
    }

    public function wechat()
    {
        return $this->belongsTo(Wechat::class, 'appid', 'appid');
    }
}
