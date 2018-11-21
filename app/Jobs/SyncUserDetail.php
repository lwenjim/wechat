<?php

namespace App\Jobs;

class SyncUserDetail extends Job
{
    public $tries = 3;
    public $timeout = 600;

    protected $openid;
    protected $appid;


    public function __construct($openid, $appid)
    {
        $this->openid = $openid;
        $this->appid = $appid;
    }

    public function handle()
    {
        batchInsertUser($this->openid, $this->appid);
    }
}
