<?php

namespace App\Transformers;

use App\Models\UserOrderLog;
use League\Fractal\TransformerAbstract;

class UserOrderLogTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(UserOrderLog $userOrderLog)
    {
        return $userOrderLog->attributesToArray();
    }

    public function includeUser(UserOrderLog $userOrderLog)
    {
        $user = $userOrderLog->user;
        if ($user) {
            return $this->item($userOrderLog->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
        } else {
            return null;
        }
    }
}
