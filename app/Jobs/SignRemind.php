<?php

namespace App\Jobs;

use App\Models\User;

class SignRemind extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $user;
    protected $appid;
    protected $content;
    protected $reply;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $content, $appid, $reply)
    {
        $this->user = $user;
        $this->appid = $appid;
        $this->content = $content;
        $this->reply = $reply;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $redis = app('redis');
        $nickname = $this->user->nickname;
        if (strpos($this->user->nickname, '\'') !== false) {
            $nickname = str_replace('\'', '', $this->user->nickname);
        }
        $this->content = str_replace('{{nickname}}', addslashes($nickname), $this->content);
        if (sendStaffMsg($this->reply, $this->content, $this->user->openid, 1, $this->appid)) {
            $redis->hincrby('mornight:remind:sign_success_num', date('Ymd'), 1);
            $redis->hset('mornight:remind:sign_success:'.date('Ymd'), $this->user->openid, date('Y-m-d H:i:s'));
        } else {
            $redis->hincrby('mornight:remind:sign_fail_num', date('Ymd'), 1);
            $redis->hset('mornight:remind:sign_fail:'.date('Ymd'), $this->user->openid, date('Y-m-d H:i:s'));
        }
    }
}
