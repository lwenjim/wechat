<?php

namespace App\Transformers;

use App\Models\CouponItem;
use League\Fractal\TransformerAbstract;

class CouponItemTransformer extends TransformerAbstract
{
    public function transform(CouponItem $couponItem)
    {
        return $couponItem->attributesToArray();
    }
}
