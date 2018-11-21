<?php

namespace App\Transformers;

use App\Models\WechatUserSubscribeLogs;
use League\Fractal\TransformerAbstract;

class WechatUserSubscribeLogsTransformer extends TransformerAbstract
{
    public function transform(WechatUserSubscribeLogs $WechatUserSubscribeLogs)
    {
        return $WechatUserSubscribeLogs->attributesToArray();
    }
}
