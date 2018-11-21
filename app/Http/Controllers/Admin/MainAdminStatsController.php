<?php
/*
|--------------------------------------------------------------------------
| 主后台统计
|--------------------------------------------------------------------------
| author: ygq
| time: 20180815
| desc: 主后台首页的所有统计接口
|
*/

namespace App\Http\Controllers\Admin;

use function config;
use Illuminate\Support\Facades\Input;

use App\Models\WechatStatsAdmin;
use App\Models\WechatStats;
use DB;
use function number_format;

class MainAdminStatsController extends AdminController
{

    public function todayTotal()
    {
        return [
            'todayTotal' => number_format(array_sum(app('redis')->zRange(config('config.RedisKey.0'), 0, -1, true))),
            'signToday' => number_format(app('redis')->get('mornight:platform:total_sign')),
            'activeToday' => number_format(WechatStats::select(DB::raw('sum(active) as activeTotal'))->where('date', date('Y-m-d'))->value('activeTotal'))
        ];
    }

    public function historyTotal()
    {
        $start = Input::get('start', date('Y-m-d', strtotime('-7 day')));
        $end = Input::get('end', date('Y-m-d'));
        if (strtotime($start) >= strtotime($end)) {
            return $this->errorBadRequest('开始时间必须小于结束时间。');
        }
        if ((strtotime($end) - strtotime($start)) > (31 * 86400)) {
            return $this->errorBadRequest('最多查询一个月的数据。');
        }
        $data = WechatStatsAdmin::where('date', '>=', $start)->where('date', '<=', $end)->pluck('user_total', 'date');
        $data = array_map(function ($row) {
            return $row;
        }, $data->toArray());
        return $data;
    }

    public function signHistory()
    {
        $start = Input::get('start', date('Y-m-d', strtotime('-7 day')));
        $end = Input::get('end', date('Y-m-d'));
        if (strtotime($start) >= strtotime($end)) {
            return $this->errorBadRequest('开始时间必须小于结束时间。');
        }
        if ((strtotime($end) - strtotime($start)) > (31 * 86400)) {
            return $this->errorBadRequest('最多查询一个月的数据。');
        }
        $data = WechatStatsAdmin::where('date', '>=', $start)->where('date', '<=', $end)->pluck('sign_total', 'date');
        return $data;
    }

    public function activeUserStatistics()
    {
        $start = $this->request->input('start', date('Y-m-d', strtotime('-7 day')));
        $end = $this->request->input('end', date('Y-m-d'));
        return WechatStats::select('date', DB::raw('sum(active) as activeTotal'))
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->groupBy('date')
            ->limit(8)
            ->get();
    }
}
