<?php

namespace App\Transformers;

use App\Models\UserCoin;
use League\Fractal\TransformerAbstract;

class UserCoinTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'admin'];

    public function transform(UserCoin $userCoin)
    {
        return $userCoin->attributesToArray();
    }

    public function includeUser(UserCoin $userCoin)
    {
        return $this->item($userCoin->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeAdmin(UserCoin $userCoin)
    {
        return $this->item($userCoin->admin()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }
}
