<?php

namespace App\Transformers;

use App\Models\UserSign;
use League\Fractal\TransformerAbstract;

class UserSignTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(UserSign $userSign)
    {
        return $userSign->attributesToArray();
    }

    public function includeUser(UserSign $userSign)
    {
        return $this->item($userSign->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }
}
