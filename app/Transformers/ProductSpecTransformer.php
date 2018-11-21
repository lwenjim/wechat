<?php

namespace App\Transformers;

use App\Models\ProductSpec;
use League\Fractal\TransformerAbstract;

class ProductSpecTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['product', 'prices', 'likes', 'spec_values'];

    public function transform(ProductSpec $spec)
    {
        return $spec->attributesToArray();
    }

    public function includeProduct(ProductSpec $spec)
    {
        $product = $spec->product()->select('id', 'title', 'image')->first();
        if ($product) {
            return $this->item($product, new ProductTransformer());
        }
    }

    public function includePrices(ProductSpec $spec)
    {
        return $this->collection($spec->prices()->select('id', 'product_spec_id', 'user_group_id', 'price', 'status')->get(), new ProductSpecPriceTransformer());
    }

    public function includeLikes(ProductSpec $spec)
    {
        return $this->collection($spec->likes()->select('id', 'product_spec_id', 'user_id')->get(), new ProductSpecLikeTransformer());
    }

    public function includeSpecValues(ProductSpec $spec)
    {
        return $this->collection($spec->spec_values()->get(), new SpecValueTransformer());
    }
}
