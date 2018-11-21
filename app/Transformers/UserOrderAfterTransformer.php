<?php

namespace App\Transformers;

use App\Models\UserOrderAfter;
use League\Fractal\TransformerAbstract;

class UserOrderAfterTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(UserOrderAfter $userOrderAfter)
    {
        return $userOrderAfter->attributesToArray();
    }

    public function includeUser(UserOrderAfter $userOrderAfter)
    {
        $user = $userOrderAfter->user;
        if ($user) {
            return $this->item($userOrderAfter->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
        } else {
            return null;
        }
    }
}
