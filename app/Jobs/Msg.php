<?php
namespace App\Jobs;

use App\Models\Msg as MsgModel;
use App\Models\MsgSession;
use App\Models\User;

class Msg extends Job
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
        $user = User::select('id', 'nickname')->where('openid', $this->msg['openid'])->first();
        if ($user) {
            $session = MsgSession::updateOrCreate(['user_id' => $user->id, 'appid' => $this->msg['appid']], ['updated_at' => date('Y-m-d H:i:s')]);
            try {
                MsgModel::create(['appid' => $this->msg['appid'],'session_id' => $session->id, 'user_id' => $user->id, 'type' => $this->msg['type'], 'content' => $this->msg['content']]);
                $redis = app('redis');
                if ($redis->hlen('wechatmsgclient')) {
                    $redis->publish('wechatmsg', $session->id);
                    $staff = true;
                } else {
                    sendStaffMsg('text', setting('auto_reply'), $this->msg['openid']);
                    $staff = false;
                }
                # dispatch((new MsgRemind('msg', ['content' => '您有一个新的客服消息，' . ($staff ? '' : '现在没有客服在线，') . '请及时处理', 'nickname' => $user->nickname]))->onQueue('remind'));
            } catch (\Exception $e) {
                dispatch(new Msg($this->msg));
            }
        }
    }
}
