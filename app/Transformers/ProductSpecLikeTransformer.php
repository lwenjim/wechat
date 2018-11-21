<?php

namespace App\Transformers;

use App\Models\ProductSpecLike;
use League\Fractal\TransformerAbstract;

class ProductSpecLikeTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'product_spec'];

    public function transform(ProductSpecLike $Like)
    {
        return $Like->attributesToArray();
    }

    public function includeUser(ProductSpecLike $Like)
    {
        $user = $Like->user()->select('id', 'openid', 'nickname', 'headimgurl')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        }
    }

    public function includeProductSpec(ProductSpecLike $Like)
    {
        $product_spec = $Like->product_spec()->first();
        if ($product_spec) {
            return $this->item($product_spec, new ProductSpecTransformer());
        }
    }
}
