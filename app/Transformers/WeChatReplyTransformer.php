<?php

namespace App\Transformers;

use App\Models\WeChatReply;
use League\Fractal\TransformerAbstract;

class WeChatReplyTransformer extends TransformerAbstract
{
    public function transform(WeChatReply $reply)
    {
        return $reply->attributesToArray();
    }
}
