<?php

namespace App\Models;

class ProductContent extends Model
{
    protected $table = 'product_content';
    public $primaryKey = 'product_id';
    public $incrementing = false;
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
