<?php

namespace App\Transformers;

use App\Models\WeChatQrcodeUser;
use League\Fractal\TransformerAbstract;

class WeChatQrcodeUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(WeChatQrcodeUser $user)
    {
        return $user->attributesToArray();
    }

    public function includeUser(WeChatQrcodeUser $user)
    {
        $userObj = $user->user()->select('id', 'openid', 'nickname', 'headimgurl')->first();
        if ($userObj) {
            return $this->item($userObj, new UserTransformer());
        } else {
            return null;
        }
    }
}
