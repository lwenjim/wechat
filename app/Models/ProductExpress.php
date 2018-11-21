<?php

namespace App\Models;

class ProductExpress extends Model
{
    protected $table = 'product_express';
    protected $casts = [
        'content' => 'array'
    ];
}
