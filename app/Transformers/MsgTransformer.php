<?php

namespace App\Transformers;

use App\Models\Msg;
use League\Fractal\TransformerAbstract;

class MsgTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(Msg $msg)
    {
        return $msg->attributesToArray();
    }

    public function includeUser(Msg $msg)
    {
        return $this->item($msg->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }
}
