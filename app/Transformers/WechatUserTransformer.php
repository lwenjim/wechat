<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/11 0011
 * Time: 17:29
 */

namespace App\Transformers;


use App\Models\WechatUser;
use League\Fractal\TransformerAbstract;

class WechatUserTransformer extends TransformerAbstract
{
    public function transform(WechatUser $wechatUser)
    {
        return $wechatUser->attributesToArray();
    }
}