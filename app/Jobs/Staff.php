<?php

namespace App\Jobs;

use App\Models\WechatStaff;

class Staff extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $staff;
    protected $appid;
    protected $openid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WechatStaff $staff, $openid, $appid = null)
    {
        $this->staff = $staff;
        $this->appid = $appid;
        $this->openid = $openid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $redis = app('redis');
        $key = 'mornight:staff-' . $this->staff->id;
        if (sendStaffMsg($this->staff->reply, $this->staff->content, $this->openid, 1, $this->appid)) {
            $redis->hincrby($key, 'staff_success', 1);
        } else {
            $redis->hincrby($key, 'staff_fail', 1);
            if ($this->staff->tpl) {
                $tpl = explode("\n", trim($this->staff->tpl));
                $data['first'] = $tpl[0];
                $data['keyword1'] = $tpl[1];
                $data['keyword2'] = date('Y-m-d H:i:s');
                $data['remark'] = $tpl[2];
                if (sendTplMsg('YZ9IZ0mLrmMmmSCRThZ2jo4zO8zTUnZq-APe99XkMpM', $tpl[3], $data, $this->openid)) {
                    $redis->hincrby($key, 'tpl_success', 1);
                } else {
                    $redis->hincrby($key, 'tpl_fail', 1);
                }
            }
        }
        if ($redis->hget($key, 'count') <= 1) {
            $this->staff->update(['staff_success' => $redis->hget($key, 'staff_success'), 'staff_fail' => $redis->hget($key, 'staff_fail'), 'tpl_success' => $redis->hget($key, 'tpl_success'), 'tpl_fail' => $redis->hget($key, 'tpl_fail')]);
            $redis->del($key);
        } else {
            $redis->hincrby($key, 'count', -1);
        }
    }
}
