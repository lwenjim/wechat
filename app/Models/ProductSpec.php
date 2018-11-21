<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class ProductSpec extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['prices', 'likes'];
    protected $table = 'product_spec';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function prices()
    {
        return $this->hasMany(ProductSpecPrice::class);
    }

    public function likes()
    {
        return $this->hasMany(ProductSpecLike::class);
    }

    public function user_order_products()
    {
        return $this->hasMany(UserOrderProduct::class);
    }

    public function spec_values()
    {
        return $this->belongsToMany(SpecValue::class, 'spec_value_product_spec', 'product_spec_id', 'spec_value_id');
    }
}
