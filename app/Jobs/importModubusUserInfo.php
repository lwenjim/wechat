<?php
/**
 * Created by PhpStorm.
 * User: Mediabook
 * Date: 2018/10/10
 * Time: 15:34
 */

namespace App\Jobs;


use App\Models\User;

class importModubusUserInfo extends Job
{
    public $tries = 3;
    public $timeout = 60;


    public $miniUserid = null;
    public $gzhUserid = null;

    public function __construct($miniUserid, $gzhUserid)
    {
        $this->miniUserid = $miniUserid;
        $this->gzhUserid = $gzhUserid;
    }

    public function handle()
    {
        $cxUser = User::find($this->miniUserid);
        $gzhUser = User::find($this->gzhUserid);
        importModubusUserInfo($cxUser, $gzhUser);
    }
}