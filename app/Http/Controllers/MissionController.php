<?php
/*
|--------------------------------------------------------------------------
| 任务
|--------------------------------------------------------------------------
| author: ygq
| time: 20180808
| desc: 每日任务、新手任务
|
*/

namespace App\Http\Controllers;

use App\Models\UserInvite;

class MissionController extends Controller
{

    public function getMission()
    {
        $user = $this->user()->switchToMiniUser();
        $redis = app('redis');
        $user_today_score = $redis->zscore('mornight:account:todayCoin:' . date('Y-m-d'), $user->id);
        if ($user_today_score) {
            $before = $redis->zcount('mornight:account:todayCoin:' . date('Y-m-d'), 0, $user_today_score);
            $total = $redis->get('mornight:platform:total_user');
            $less_percent = floor(($total - $before) / $total * 100);
        } else {
            $user_today_score = 0;
            $less_percent = 0;
        }

        //原力值和等级
        $day21_total = calculateCoin($user->id);
        $level = calculateLevel($day21_total);
        $growth_speed = calculateDiamond($level);
        $next_level = $level == 'lv1' ? 'lv2' : ($level == 'lv2' ? 'lv3' : 'lv4');

        //升级进度
        $upgrad_coin = $next_level == 'lv2' ? 500 : ($next_level == 'lv3' ? 3000 : 4500);
        $upgrade_percent = $day21_total > $upgrad_coin ? 100 : floor($day21_total / $upgrad_coin * 100);

        //点赞
        $like = $redis->get('mornight:account:likeToday:' . $user->id);
        $like = is_numeric($like) ? ($like > 3 ? 3 : $like) : 0;

        //打卡
        $time = date('H.i');
        $score = $time > 5 && $time < 8 ? 100 : 50;
        $sign = $user->getOrderCount() ? 1 : 0;

        //留言
        $message = $redis->exists('mornight:account:likeMessage:' . $user->id);

        //阅读
        $preKey = "mornight:account:todayRead:{$user->id}:";
        $preReadCount = (int)$redis->get($preKey . 'pre');
        $nexReadCount = (int)$redis->get($preKey . 'nex');

        //打卡
        $step_exchange = $user->walks()->where('date', date('Y-m-d'))->exists();

        //新手任务
        $fisher = fisherMission($user->id);

        //邀请
        $userInvite = UserInvite::where(['user_id' => $user->id])->count();
        return [
            'title' => [
                'coin' => $user_today_score,
                'level' => $level,
                'next_level' => $next_level,
                'beyond' => $less_percent,
                'upgrade_percent' => $upgrade_percent,
                'growth_speed' => $growth_speed,
            ],
            'list' => [
                'like' => $like,
                'sign' => [
                    'time' => date('H.i.s'),
                    'score' => $score,
                    'status' => $sign
                ],
                'message' => $message,
                'pre_read_count' => $preReadCount,
                'nex_read_count' => $nexReadCount,
                'step_exchange' => $step_exchange,
                'userInvite' => $userInvite,

            ],
            'fisher' => [
                'sign' => $fisher->sign,
                'read' => $fisher->read,
                'message' => $fisher->message,
                'step_exchange' => $fisher->step_exchange,
            ]
        ];
    }

}