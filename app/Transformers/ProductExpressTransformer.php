<?php

namespace App\Transformers;

use App\Models\ProductExpress;
use League\Fractal\TransformerAbstract;

class ProductExpressTransformer extends TransformerAbstract
{
    public function transform(ProductExpress $productExpress)
    {
        return $productExpress->attributesToArray();
    }
}
