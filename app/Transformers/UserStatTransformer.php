<?php

namespace App\Transformers;

use App\Models\UserStat;
use League\Fractal\TransformerAbstract;

class UserStatTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(UserStat $userStat)
    {
        return $userStat->attributesToArray();
    }

    public function includeUser(UserStat $userStat)
    {
        return $this->item($userStat->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }
}
