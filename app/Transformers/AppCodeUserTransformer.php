<?php

namespace App\Transformers;

use App\Models\AppCodeUser;
use League\Fractal\TransformerAbstract;

class AppCodeUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(AppCodeUser $user)
    {
        return $user->attributesToArray();
    }

    public function includeUser(AppCodeUser $user)
    {
        $userObj = $user->user()->select('id', 'openid', 'nickname', 'headimgurl')->first();
        if ($userObj) {
            return $this->item($userObj, new UserTransformer());
        } else {
            return null;
        }
    }
}
