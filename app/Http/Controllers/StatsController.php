<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSign;
use App\Models\WechatStats;
use App\Models\Wechat;
use DB;

class StatsController extends Controller
{
    public function signStats()
    {
        if (Input::has('start') && Input::get('start')) {
            $start = Input::get('start');
        } else {
            $start = date('Y-m-d', strtotime("-6 days"));
        }
        if (Input::has('end') && Input::get('end')) {
            $end = Input::get('end', strtotime("+1 days"));
        } else {
            $end = date('Y-m-d', strtotime("+1 days"));
        }
        $data = WechatStats::select(DB::raw('date as mydate'), DB::raw('sign as cnt'))
            ->where('appid', Auth::user()->cur_appid)
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        return $data;
    }

    public function dauStats()
    {
        if (Input::has('start') && Input::get('start')) {
            $start = Input::get('start');
        } else {
            $start = date('Y-m-d', strtotime("-6 days"));
        }
        if (Input::has('end') && Input::get('end')) {
            $end = Input::get('end', strtotime("+1 days"));
        } else {
            $end = date('Y-m-d', strtotime("+1 days"));
        }
        $data = WechatStats::select(DB::raw('date as mydate'), DB::raw('active as cnt'))
            ->where('appid', Auth::user()->cur_appid)
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        return $data;
    }

    public function totalStats()
    {
        if (Input::has('start') && Input::get('start')) {
            $start = Input::get('start');
        } else {
            $start = date('Y-m-d', strtotime("-6 days"));
        }
        if (Input::has('end') && Input::get('end')) {
            $end = Input::get('end', strtotime("+1 days"));
        } else {
            $end = date('Y-m-d', strtotime("+1 days"));
        }
        $data = WechatStats::select(DB::raw('date as mydate'), DB::raw('count as cnt'))
            ->where('appid', Auth::user()->cur_appid)
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        return $data;
    }

    public function inviteStats()
    {
        if (Input::has('start') && Input::get('start')) {
            $start = Input::get('start');
        } else {
            $start = date('Y-m-d', strtotime("-6 days"));
        }
        if (Input::has('end') && Input::get('end')) {
            $end = Input::get('end', strtotime("+1 days"));
        } else {
            $end = date('Y-m-d', strtotime("+1 days"));
        }
        $data = WechatStats::select(DB::raw('date as mydate'), DB::raw('invite as cnt'))
            ->where('appid', Auth::user()->cur_appid)
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        return $data;
    }

    public function growStats()
    {
        if (Input::has('start') && Input::get('start')) {
            $start = Input::get('start');
        } else {
            $start = date('Y-m-d', strtotime("-6 days"));
        }
        if (Input::has('end') && Input::get('end')) {
            $end = Input::get('end', strtotime("+1 days"));
        } else {
            $end = date('Y-m-d', strtotime("+1 days"));
        }
        $data = WechatStats::select(DB::raw('date as mydate'), DB::raw('grow as cnt'))
            ->where('appid', Auth::user()->cur_appid)
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        return $data;
    }

    public function pageviewStats()
    {
        $redis = app('redis');
        if ($this->request->isMethod('post')) {
            //日均总量
            $redis->incr('mornight:pv_stats:' . 'total:' . date('Y-m-d'));
            //日均总量(appid)
            $redis->incr('mornight:pv_stats:' . Auth::user()->cur_appid . ':' . 'total:' . date('Y-m-d'));
            //该页面日均总量
            $redis->incr('mornight:pv_stats:' . Auth::user()->cur_appid . ':' . Input::get('path') . ':' . date('Y-m-d'));
            return 1;
        } else {
            if (Input::has('start') && Input::get('start')) {
                $start = Input::get('start');
            } else {
                $start = date('Y-m-d', strtotime("-6 days"));
            }
            if (Input::has('end') && Input::get('end')) {
                $end = Input::get('end', strtotime("+1 days"));
            } else {
                $end = date('Y-m-d', strtotime("+1 days"));
            }
            $dates = [];
            array_push($dates, $start);
            do {
                $nextDate = date('Y-m-d', strtotime($start . ' +1 days'));
                array_push($dates, $nextDate);
                $start = $nextDate;
            } while ($start != $end);

            foreach ($dates as $key => $value) {
                $data[$key]['mydate'] = $value;
                $data[$key]['cnt'] = $redis->get('mornight:pv_stats:' . Auth::user()->cur_appid . ':' . 'total:' . $value);
            }
            return $data;
        }
    }

    public function advertData()
    {
        //判断时间格式，防止注入
        $validator = \Validator::make($this->request->input(), [
            'start' => 'required|date',
            'end' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $start = $this->request->input('start') . ' 00:00:00';
        $end = $this->request->input('end') . ' 23:59:59';
        $data = Wechat::select('id', 'name', 'bd_code', \DB::raw("(select count(1) from user_sign where user_sign.appid = wechat.appid and date>='" . $start . "' and date <= '" . $end . "') as num"))->get();
        return $data;
    }

    /**
     * 总用户数,当日打卡数,当日活跃数统计接口
     * @return array
     */
    public function todayTotal()
    {
        if (isset(Auth::user()->config['admin']) && Auth::user()->config['admin'] == 1) {
            return [
                'todayTotal' => number_format(array_sum(app('redis')->zRange(config('config.RedisKey.0'), 0, -1, true))),
                'signToday' => number_format(app('redis')->get('mornight:platform:total_sign')),
                'activeToday' => number_format(WechatStats::select(DB::raw('sum(active) as activeTotal'))->where('date', date('Y-m-d'))->value('activeTotal'))
            ];
        } else {
            return [];
        }
    }
}
