<?php

namespace App\Transformers;

use App\Models\UserLike;
use League\Fractal\TransformerAbstract;

class UserLikeTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'to_user'];

    public function transform(UserLike $userLike)
    {
        return $userLike->attributesToArray();
    }

    public function includeUser(UserLike $userLike)
    {
        return $this->item($userLike->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeToUser(UserLike $userLike)
    {
        $user = $userLike->to_user()->select('id', 'nickname', 'headimgurl', 'subscribe', 'coin', 'day')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        } else {
            return null;
        }
    }
}
