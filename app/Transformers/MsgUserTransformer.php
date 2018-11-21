<?php

namespace App\Transformers;

use App\Models\MsgUser;
use League\Fractal\TransformerAbstract;

class MsgUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(MsgUser $msgUser)
    {
        return $msgUser->attributesToArray();
    }

    public function includeUser(MsgUser $msgUser)
    {
        return $this->item($msgUser->user()->select('id','nickname','headimgurl')->first(), new UserTransformer());
    }
}
