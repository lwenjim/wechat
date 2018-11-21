<?php

namespace App\Transformers;

use App\Models\KefuConnMsg;
use League\Fractal\TransformerAbstract;

class KefuConnMsgTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(KefuConnMsg $KefuConnMsg)
    {
        return $KefuConnMsg->attributesToArray();
    }

    public function includeUser(KefuConnMsg $KefuConnMsg)
    {
        return $this->item($KefuConnMsg->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }
}
