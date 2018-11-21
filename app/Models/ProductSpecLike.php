<?php

namespace App\Models;

class ProductSpecLike extends Model
{
    protected $table = 'product_spec_like';
    public $timestamps = false;

    public function product_spec()
    {
        return $this->belongsTo(ProductSpec::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
