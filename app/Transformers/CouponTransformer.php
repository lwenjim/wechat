<?php

namespace App\Transformers;

use App\Models\Coupon;
use League\Fractal\TransformerAbstract;

class CouponTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['items', 'products'];

    public function transform(Coupon $coupon)
    {
        return $coupon->attributesToArray();
    }

    public function includeItems(Coupon $coupon)
    {
        return $this->collection($coupon->items()->get(), new CouponItemTransformer());
    }

    public function includeProducts(Coupon $coupon)
    {
        return $this->collection($coupon->products()->select('id', 'title', 'short_title')->where('status', 1)->orderBy('sort', 'asc')->get(), new ProductTransformer());
    }
}
