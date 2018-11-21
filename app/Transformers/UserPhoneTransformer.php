<?php

namespace App\Transformers;

use App\Models\UserPhone;
use League\Fractal\TransformerAbstract;

class UserPhoneTransformer extends TransformerAbstract
{
    public function transform(UserPhone $userPhone)
    {
        return $userPhone->attributesToArray();
    }
}
