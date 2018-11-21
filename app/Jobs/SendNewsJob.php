<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/7 0007
 * Time: 14:50
 */

namespace App\Jobs;


class SendNewsJob extends Job
{
    public $tries = 3;
    public $timeout = 60;
    private $appid;
    private $openid;
    private $message;

    public function __construct($appid, $openid, $message)
    {
        $this->appid = $appid;
        $this->openid = $openid;
        $this->message = $message;
    }

    public function handle()
    {
        getApp($this->appid)->staff->message($this->message)->to($this->openid)->send();
    }
}