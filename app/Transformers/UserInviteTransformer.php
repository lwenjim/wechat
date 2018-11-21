<?php

namespace App\Transformers;

use App\Models\UserInvite;
use League\Fractal\TransformerAbstract;

class UserInviteTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'invite_user'];

    public function transform(UserInvite $userInvite)
    {
        return $userInvite->attributesToArray();
    }

    public function includeUser(UserInvite $userInvite)
    {
        $user = $userInvite->user()->select('id', 'nickname', 'headimgurl')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        } else {
            return null;
        }
    }

    public function includeInviteUser(UserInvite $userInvite)
    {
        $user = $userInvite->invite_user()->select('id', 'nickname', 'headimgurl', 'subscribe', 'coin', 'day')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        } else {
            return null;
        }
    }
}
