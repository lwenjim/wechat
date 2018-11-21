<?php

namespace App\Transformers;

use App\Models\UserCart;
use League\Fractal\TransformerAbstract;

class UserCartTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'product', 'product_spec'];

    public function transform(UserCart $userCart)
    {
        return $userCart->attributesToArray();
    }

    public function includeUser(UserCart $userCart)
    {
        return $this->item($userCart->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeProduct(UserCart $userCart)
    {
        return $this->item($userCart->product()->select('id', 'short_title', 'image')->first(), new ProductTransformer());
    }

    public function includeProductSpec(UserCart $userCart)
    {
        return $this->item($userCart->product_spec()->first(), new ProductSpecTransformer());
    }
}
