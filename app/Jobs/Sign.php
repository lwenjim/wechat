<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserSign;
use App\Models\WechatUser;
use App\Services\Card;
use function info;

class Sign extends Job
{
    public $tries = 1;
    public $timeout = 60;
    protected $miniUser;
    protected $sign;
    protected $plus;
    protected $msg;
    protected $city;
    protected $diamond;
    protected $gzhId;

    public function __construct($gzhId, User $user, UserSign $sign, $plus, $diamond, $msg, $city = '')
    {
        $this->miniUser = $user;
        $this->sign = $sign;
        $this->plus = $plus;
        $this->msg = $msg;
        $this->city = $city;
        $this->diamond = $diamond;
        $this->gzhId = $gzhId;
    }

    public function handle()
    {
        $days = $this->miniUser->signs()->count();
        $city = $this->city ?? $this->miniUser->city;
        $card = Card::achieve($this->miniUser, $city, $days, $this->sign->day, $this->sign->created_at, $this->miniUser->last_appid);
        sendStaffMsg('image', $card, $this->miniUser->last_openid, 1, $this->miniUser->last_appid);
        $this->miniUser->day = $this->sign->day;
        if ($this->miniUser->save()) {
            sendMsg($this->gzhId, '原力消息提醒', 'sign', $this->msg);
            changeCoin($this->miniUser->id, $this->plus, 'sign', $this->sign->id, '打卡送原力');
            $wechatId = WechatUser::where(['user_id'=>$this->gzhId])->value('wechat_id');
            incrementBlueDiamond($wechatId, $this->miniUser->id, $this->diamond, '打卡获得蓝钻');
            fisherMissionAward($wechatId, $this->miniUser->id, 'sign');
            if ($this->miniUser->sign_years()->where('year', date('Y'))->count()) {
                $this->miniUser->sign_years()->where('year', date('Y'))->increment('number');
            } else {
                $this->miniUser->sign_years()->create(['number' => 1, 'year' => date('Y')]);
            }
            UserSign::cacheFlush();
        }
    }
}
