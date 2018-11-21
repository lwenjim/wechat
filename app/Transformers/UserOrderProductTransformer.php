<?php

namespace App\Transformers;

use App\Models\UserOrderProduct;
use League\Fractal\TransformerAbstract;

class UserOrderProductTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['after', 'product_spec'];

    public function transform(UserOrderProduct $userOrderProduct)
    {
        return $userOrderProduct->attributesToArray();
    }

    public function includeAfter(UserOrderProduct $userOrderProduct)
    {
        $after = $userOrderProduct->after()->select('id', 'user_order_product_id', 'type', 'status')->first();
        if ($after) {
            return $this->item($after, new UserOrderAfterTransformer());
        }
    }

    public function includeProductSpec(UserOrderProduct $userOrderProduct)
    {
        $productSpec = $userOrderProduct->product_spec()->select('id', 'price')->first();
        if ($productSpec) {
            return $this->item($productSpec, new ProductSpecTransformer());
        }
    }
}
