<?php

namespace App\Transformers;

use App\Models\ProductPart;
use League\Fractal\TransformerAbstract;

class ProductPartTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['products'];

    public function transform(ProductPart $productPart)
    {
        return $productPart->attributesToArray();
    }

    public function includeProducts(ProductPart $productPart)
    {
        return $this->collection($productPart->products()->select('id', 'product_part_id', 'title', 'short_title', 'image', 'order', 'sort', 'created_at')->where('status', 1)->orderBy('sort', 'asc')->get(), new ProductTransformer());
    }
}
