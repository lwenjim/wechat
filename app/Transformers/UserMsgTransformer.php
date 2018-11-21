<?php

namespace App\Transformers;

use App\Models\UserMsg;
use League\Fractal\TransformerAbstract;

class UserMsgTransformer extends TransformerAbstract
{
    public function transform(UserMsg $userMsg)
    {
        return $userMsg->attributesToArray();
    }
}
