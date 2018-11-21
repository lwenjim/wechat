<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Coupon extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['items'];
    protected $table = 'coupon';

    public function items()
    {
        return $this->hasMany(CouponItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
