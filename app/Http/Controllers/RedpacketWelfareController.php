<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13 0013
 * Time: 17:35
 */

namespace App\Http\Controllers;
use App\Models\UserRedPacketAdminInfo;

class RedpacketWelfareController extends Controller
{
    public function join()
    {
        $user = $this->user()->switchToMiniUser();
        $builder = $user->redPacketUser()->where(['openid' => $user->openid,]);
        $redis = app('redis');
        $redisKey = 'redPacketUser:code';
        if ($redis->sismember($redisKey, $user->openid)) {
            return $this->errorBadRequest('已经参与');
        }
        $redis->sadd($redisKey, $user->openid);
        $builder->updateOrCreate(['openid' => $user->openid,], ['receive_time' => date('Y-m-d H:i:s'), 'appid' => $user->last_appid]);
        $this->noContent();
    }

    public function redPacketUserInfo()
    {
        $user = $this->user()->switchToMiniUser();
        return $user->redPacketUser()->where(['openid' => $user->openid,])->first();
    }

    public static function fetchQrcode()
    {
        return UserRedPacketAdminInfo::where('user_id', 1)->select('qrcode')->first();
    }

    public function validateLink($code)
    {
        $user = $this->user()->switchToMiniUser();
        $builder = $user->redPacketUser()->where(['openid' => $user->openid,]);
        if (!$builder->exists()) {
            return $this->errorBadRequest('你未参与');
        }
        $redPacketUser = $builder->first();
        if (!empty($redPacketUser->validate_time)) {
            return $this->errorBadRequest('你已经验证过');
        }
        if (empty($redPacketUser->validate_secret)) {
            return $this->errorBadRequest('未获取校验连接');
        }
        if ($code != substr(md5(md5($user->openid) . $redPacketUser->validate_secret), 10, 5)) {
            return $this->errorBadRequest('验证失败');
        }
        $redPacketUser->update(['validate_time' => date('Y-m-d H:i:s'),]);
        $this->noContent();
    }
}