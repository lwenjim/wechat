<?php

namespace App\Transformers;

use App\Models\ProductComment;
use League\Fractal\TransformerAbstract;

class ProductCommentTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'product', 'product_spec'];

    public function transform(ProductComment $Comment)
    {
        return $Comment->attributesToArray();
    }

    public function includeUser(ProductComment $Comment)
    {
        $user = $Comment->user()->select('id', 'openid', 'nickname', 'headimgurl')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        }
    }

    public function includeProduct(ProductComment $Comment)
    {
        return $this->item($Comment->product()->select('id', 'title', 'short_title', 'image')->first(), new ProductTransformer());
    }

    public function includeProductSpec(ProductComment $Comment)
    {
        $product_spec = $Comment->product_spec()->first();
        if ($product_spec) {
            return $this->item($product_spec, new ProductSpecTransformer());
        }
    }
}
