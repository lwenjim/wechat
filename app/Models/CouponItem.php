<?php

namespace App\Models;

class CouponItem extends Model
{
    protected $table = 'coupon_item';

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
