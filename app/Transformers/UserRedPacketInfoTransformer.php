<?php

namespace App\Transformers;

use App\Models\UserRedPacketInfo;
use League\Fractal\TransformerAbstract;

class UserRedPacketInfoTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user','wechat'];
    public function transform(UserRedPacketInfo $UserRedPacketInfo)
    {
        return $UserRedPacketInfo->attributesToArray();
    }

    public function includeUser(UserRedPacketInfo $UserRedPacketInfo)
    {
        return $this->item($UserRedPacketInfo->user()->select('id','nickname','headimgurl')->first(), new UserTransformer());
    }

    public function includeWechat(UserRedPacketInfo $UserRedPacketInfo)
    {
        return $this->item($UserRedPacketInfo->wechat()->select('name')->first(), new WechatTransformer());
    }
}
