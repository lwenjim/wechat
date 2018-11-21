<?php

namespace App\Transformers;

use App\Models\ProductContent;
use League\Fractal\TransformerAbstract;

class ProductContentTransformer extends TransformerAbstract
{
    public function transform(ProductContent $content)
    {
        return $content->attributesToArray();
    }
}
