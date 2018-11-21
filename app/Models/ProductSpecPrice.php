<?php

namespace App\Models;

class ProductSpecPrice extends Model
{
    protected $table = 'product_spec_price';

    public function product_spec()
    {
        return $this->belongsTo(ProductSpec::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
