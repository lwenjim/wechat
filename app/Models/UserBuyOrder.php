<?php

namespace App\Models;

class UserBuyOrder extends Model
{
    protected $table = 'user_buy_order';
    protected $casts = [
        'data' => 'array'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buy()
    {
        return $this->belongsTo(Buy::class);
    }
}
