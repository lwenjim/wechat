<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserOrderAfter extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'user_order_after';
    protected $casts = [
        'images' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_order()
    {
        return $this->belongsTo(UserOrder::class, 'user_order_id');
    }

    public function user_order_product()
    {
        return $this->belongsTo(UserOrderProduct::class, 'user_order_product_id');
    }
}
