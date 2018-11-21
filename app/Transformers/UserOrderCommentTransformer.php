<?php

namespace App\Transformers;

use App\Models\UserOrderComment;
use League\Fractal\TransformerAbstract;

class UserOrderCommentTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(UserOrderComment $userOrderComment)
    {
        return $userOrderComment->attributesToArray();
    }

    public function includeUser(UserOrderComment $userOrderComment)
    {
        $user = $userOrderComment->user()->select('id', 'nickname', 'headimgurl')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        } else {
            return null;
        }
    }
}
