<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/15 0015
 * Time: 23:15:17
 */

namespace App\Jobs;


class RemainMornightUpDayLast extends Job
{
    public $tries = 3;
    public $timeout = 60;

    protected $appid = null;
    protected $openid = null;
    protected $content = null;

    public function __construct($appid, $openid, $content)
    {
        $this->appid = $appid;
        $this->openid = $openid;
        $this->content = $content;
    }

    public function handle()
    {
        $wechatUser = \App\Models\WechatUser::where(['openid' => $this->openid])->first();
        if (!empty($wechatUser) && $wechatUser->subscribe != 1 || empty($openid)) return false;
        getApp($this->appid)->staff->message($this->content)->to($this->openid)->send();
    }
}