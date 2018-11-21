<?php

namespace App\Http\Controllers\Activity;

class WheelController extends ActivityController
{
    public function wheel()
    {
        $user = $this->user();
        if ($user->msgs()->where('type', 'wheel')->count()) {
            return $this->errorBadRequest('您已经玩过了！');
        }
        $_prize = [
            'coin_100' => 800,
            'coin_500' => 100,
            'coin_1000' => 92,
            'yuan_1' => 5,
            'yuan_5' => 2,
            'yuan_10' => 1,
        ];
        $prize = $this->getPrize($_prize);
        list($type, $num) = explode('_', $prize);
        if ($type == 'coin') {
            //魔币变化
            changeCoin($user->id, $num, 'wheel', 1, '玩新手大转盘送魔币');
            //消息提醒
            sendMsg($user->id, '魔币消息提醒', 'wheel', '恭喜您参与新手大转盘获得' . $num . '魔币');
        } else {
            $mch_billno = 'wheel' . date('YmdHis') . mt_rand(10, 99);
            $luckyMoney = app('wechat')->lucky_money;
            $luckyMoneyData = [
                'mch_billno' => $mch_billno,
                'send_name' => '魔都巴士新手大转盘',
                're_openid' => $user->openid,
                'total_amount' => $num * 100,  //单位为分，不小于300
                'wishing' => '领取成功',
                'act_name' => '新手大转盘',
                'remark' => '红包备注',
            ];
            try {
                $result = $luckyMoney->sendNormal($luckyMoneyData);
                if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    //消息提醒
                    sendMsg($user->id, '新手大转盘消息提醒', 'wheel', '恭喜您参与新手大转盘获得' . $num . '元红包');
                } else {
                    info($mch_billno . ':' . $user->id . ':' . $result['return_msg']);
                }
            } catch (\Exception $e) {
                info($mch_billno . ':' . $user->id . ':' . $e->getMessage());
            }
        }
        return ['prize' => $type, 'num' => $num];
    }
}