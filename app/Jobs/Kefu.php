<?php

namespace App\Jobs;

use App\Models\Kefu as KefuModel;
use App\Models\KefuConn;

class Kefu extends Job
{
    public $tries = 3;
    public $timeout = 60;
    protected $msg;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $redis = app('redis');
        $kefu_id = 0;
        $kefu_ids = KefuModel::where('status', 1)->pluck('id')->toArray();
        foreach ($kefu_ids as $kefu_id) {
            if ($redis->exists('mornight:kefu' . $kefu_id)) {
                break;
            }
        }
        if ($kefu_id == 0) {
            $kefu_id = KefuModel::where('is_default', 1)->value('id');
            //sendStaffMsg('text', setting('auto_reply'), $this->msg['openid']);
        }
        $kefuconn = KefuConn::updateOrCreate(['user_id' => $this->msg['user_id'], 'kefu_id' => $kefu_id], ['updated_at' => date('Y-m-d H:i:s')]);
        $kefuconn->msgs()->create(['user_id' => $this->msg['user_id'], 'openid' => $this->msg['openid'], 'type' => $this->msg['type'], 'origin' => $this->msg['origin'], 'session' => $this->msg['session'], 'content' => $this->msg['content']]);
        $redis->publish('mornight:kefu' . $kefu_id, $kefu_id);
    }
}
