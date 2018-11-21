<?php

namespace App\Http\Controllers;

use App\Models\Wechat;
use App\Models\WechatUserSubscribeLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WechatUserSubscribeLogController extends Controller
{
    function index()
    {
        $where['start'] = trim($this->request->get('start'));
        $where['end'] = trim($this->request->get('end'));

        $reg = '/\d{4}\-\d{1,2}\-\d{1,2}/';
        $valid_start = preg_match($reg, $where['start']);
        $valid_end = preg_match($reg, $where['end']);
        if (!$valid_start && !$valid_end) {
            $where['start'] = strtotime('-1 month');
            $where['end'] = time();
        } else if (!($valid_start && $valid_end)) {
            if (!$valid_start) {
                $where['start'] = strtotime($where['end'] . ' -1 month');
            }
            if (!$valid_end) {
                $where['end'] = strtotime($where['start'] . ' +1 month');
            }
        } else {
            $where['start'] = strtotime($where['start']);
            $where['end'] = strtotime($where['end']);
        }
        $logs = WechatUserSubscribeLogs::
        select(DB::raw('substr(created_at,1,10) as mydate'), DB::raw('count(1) as cnt'))
            ->where('created_at', '>', date('Y-m-d', $where['start']))
            ->where('created_at', '<=', date('Y-m-d', $where['end'] + 86400))
            ->where('appid', '=', Auth::user()->cur_appid)
            ->groupBy('mydate')
            ->orderBy('mydate', 'asc')
            ->get();
        return $logs;
    }

    public function statistics()
    {
        return Wechat::where(['appid' => $this->user()->cur_appid])->value('statistics');
    }
}
