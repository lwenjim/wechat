<?php

namespace App\Transformers;

use App\Models\PlatformApply;
use League\Fractal\TransformerAbstract;

class PlatFormApplyTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['Wechat', 'TokenUser'];
    public function transform(PlatformApply $PlatformApply)
    {
        return $PlatformApply->attributesToArray();
    }

    function includeWechat(PlatformApply $PlatformApply)
    {
        return $this->item($PlatformApply->wechat()->first(), new WechatTransformer());
    }

    function includeTokenUser(PlatformApply $PlatformApply)
    {
        return $this->item($PlatformApply->tokenuser()->first(), new TokenUserTransformer());
    }
}
