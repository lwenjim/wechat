<?php

namespace App\Models;

class ProductPart extends Model
{
    protected $table = 'product_part';

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function user_order_products()
    {
        return $this->hasMany(UserOrderProduct::class);
    }
}
