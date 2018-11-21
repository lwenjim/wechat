<?php

namespace App\Jobs;

use App\Models\User;

class WalkRemind extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $user;
    protected $appid;
    protected $content;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $content, $appid = null)
    {
        $this->user = $user;
        $this->appid = $appid;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $redis = app('redis');
        if (strpos($this->user->nickname, '\'') !== false) {
            $nickname = str_replace('\'', '', $this->user->nickname);
        } else {
            $nickname = $this->user->nickname;
        }
        $this->content = str_replace('{{nickname}}', addslashes($nickname), $this->content);
        if (sendStaffMsg('news', $this->content, $this->user->openid, 1, $this->appid)) {
            $redis->hset('mornight:remind:walk_success', $this->user->openid, date('Y-m-d H:i:s'));
            if ($redis->ttl('mornight:remind:walk_success') == -1) {
                $redis->expire('mornight:remind:walk_success', strtotime(date('Y-m-d', strtotime('+1 day'))) - time());
            }
        } else {
            $redis->hset('mornight:remind:walk_fail', $this->user->openid, date('Y-m-d H:i:s'));
            if ($redis->ttl('mornight:remind:walk_fail') == -1) {
                $redis->expire('mornight:remind:walk_fail', strtotime(date('Y-m-d', strtotime('+1 day'))) - time());
            }
        }
    }
}
