<?php

namespace App\Transformers;

use App\Models\UserBlueDiamond;
use League\Fractal\TransformerAbstract;

class UserDiamondTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'admin'];

    public function transform(UserBlueDiamond $userBlueDiamond)
    {
        return $userBlueDiamond->attributesToArray();
    }

    public function includeUser(UserBlueDiamond $userBlueDiamond)
    {
        return $this->item($userBlueDiamond->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

}
