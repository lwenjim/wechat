<?php

namespace App\Transformers;

use App\Models\UserAddress;
use League\Fractal\TransformerAbstract;

class UserAddressTransformer extends TransformerAbstract
{
    public function transform(UserAddress $userAddress)
    {
        return $userAddress->attributesToArray();
    }
}
