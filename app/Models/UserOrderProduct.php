<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserOrderProduct extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'user_order_product';

    public function after()
    {
        return $this->hasOne(UserOrderAfter::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_spec()
    {
        return $this->belongsTo(ProductSpec::class);
    }

    public function user_order()
    {
        return $this->belongsTo(UserOrder::class, 'user_order_id');
    }
}
