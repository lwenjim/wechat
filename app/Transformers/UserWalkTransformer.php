<?php

namespace App\Transformers;

use App\Models\UserWalk;
use League\Fractal\TransformerAbstract;

class UserWalkTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'gives', 'to_gives'];

    public function transform(UserWalk $userWalk)
    {
        return $userWalk->attributesToArray();
    }

    public function includeUser(UserWalk $userWalk)
    {
        return $this->item($userWalk->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeGives(UserWalk $userWalk)
    {
        return $this->collection($userWalk->gives()->orderBy('created_at', 'desc')->get(), new UserWalkGiveTransformer());
    }

    public function includeToGives(UserWalk $userWalk)
    {
        return $this->collection($userWalk->to_gives()->orderBy('created_at', 'desc')->get(), new UserWalkGiveTransformer());
    }
}
