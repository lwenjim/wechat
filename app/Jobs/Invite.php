<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserInvite;
use App\Models\Wechat;
use function config;

class Invite extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $user;
    protected $id;
    protected $appid;

    public function __construct($user, $id, $appid)
    {
        $this->user = $user;
        $this->id = $id;
        $this->appid = $appid;
    }

    public function handle()
    {
        $plus = config('config.InviteUserGainCoin');
        changeCoin($this->id, $plus, 'invite', $this->user->id, '邀请好友送原力');
        sendMsg($this->id, '原力消息提醒', 'invite', '邀请好友' . $this->user->nickname . '成功,恭喜您获得' . $plus . '原力');
        UserInvite::create(['user_id' => $this->id, 'invite_user_id' => $this->user->id, 'appid' => $this->appid]);
    }
}
