<?php

namespace App\Models;

class ProductComment extends Model
{
    protected $table = 'product_comment';
    protected $casts = [
        'images' => 'array'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_spec()
    {
        return $this->belongsTo(ProductSpec::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
