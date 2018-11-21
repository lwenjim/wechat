<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInvite;
use App\Models\UserLike;
use App\Models\UserSign;
use App\Models\UserSignYear;
use App\Models\UserWalk;
use App\Models\UserBlueDiamond;
use DB;

class RankController extends Controller
{
    public function blueDiamond($num = 0)
    {
        $userIds = $this->getUserIds();
        $user = $this->user()->switchToMiniUser();
        $users = UserBlueDiamond::select('user_id', \DB::raw('SUM(number) total_blue_diamond'))->with(['user' => function ($query) {
            $query->select('id', 'nickname', 'headimgurl');
        }])->withCount(['to_likes AS like_count' => function ($query) {
            $query->where('created_at', '>', date('Y-m-d'))->where('type', 'blueDiamond');
        }, 'to_likes AS liked_count' => function ($query) {
            $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'blueDiamond');
        }])->whereIn('user_id', $userIds)->groupBy('user_id')->offset($num)->limit(20)->orderBy('total_blue_diamond', 'desc')->get();

        $user_ids = UserBlueDiamond::select('user_id', \DB::raw('SUM(number) total_blue_diamond'))->whereIn('user_id', $userIds)->groupBy('user_id')->orderBy('total_blue_diamond', 'desc')->pluck('user_id')->toArray();
        $order = in_array($user->id, $user_ids) ? array_search($user->id, $user_ids) : -1;
        $userBluediamond = UserBlueDiamond::where('user_id', $user->id)->sum('number');
        return ['list' => $users, 'order' => $order, 'totalBlueDiamond' => $userBluediamond];
    }

    public function signTime($num = 0)
    {
        $signTime['sort'] = -1;
        $signTime['list'] = UserSign::select(['user_id', 'created_at'])->where('appid', $this->user()->switchToMiniUser()->last_appid)->where('date', date('Y-m-d'))->with(['user' => function ($query){
            $query->select('id', 'nickname', 'headimgurl')->withCount(['to_likes AS like_count' => function ($query){
                $query->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_time');
            }, 'to_likes AS liked_count' => function ($query) {
                $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_time');
            }]);
        }])->offset($num)->limit(20)->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

        $sign = UserSign::select('user_id', 'date', 'created_at')->where('appid', $this->user()->switchToMiniUser()->last_appid)->where('user_id', $this->user()->switchToMiniUser()->id)->where('date', date('Y-m-d'))->with(['user' => function ($query) {
            $query->select('id', 'nickname', 'headimgurl')->withCount(['to_likes AS like_count' => function ($query) {
                $query->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_time');
            }, 'to_likes AS liked_count' => function ($query) {
                $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_time');
            }]);
        }])->first();
        if ($sign) {
            $signTime['sort'] = UserSign::where('date', date('Y-m-d'))->where('appid', $this->user()->switchToMiniUser()->last_appid)->where('created_at', '<', $sign->created_at)->count();
        }
        $signTime['sign'] = $sign;
        return $signTime;
    }

    public function signDayYear($year, $num = 0)
    {
        $user = $this->user()->switchToMiniUser();
        $signDayYear['list'] = UserSignYear::select(['user_id', 'number'])->whereIn('user_id', $this->getUserIds())->where('year', $year)->with(['user' => function ($query) {
            $query->select('id', 'nickname', 'headimgurl')->withCount(['to_likes AS like_count' => function ($query) {
                $query->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_day_' . date('Y'));
            }, 'to_likes AS liked_count' => function ($query) {
                $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_day_' . date('Y'));
            }]);
        }])->offset($num)->limit(20)->orderBy('number', 'desc')->orderBy('user_id', 'asc')->get();
        $signDayYear['day_year'] = $user->sign_years()->where('year', $year)->value('number');

        $user_ids = UserSignYear::select(['user_id', 'number'])->whereIn('user_id', $this->getUserIds())->where('year', $year)->orderBy('number', 'desc')->orderBy('user_id', 'asc')->pluck('user_id')->toArray();
        $signDayYear['sort'] = in_array($user->id, $user_ids) ? array_search($user->id, $user_ids) : -1;
        return $signDayYear;
    }

    public function signDay($num = 0)
    {
        $user = $this->user()->switchToMiniUser();
        $signDay['list'] = User::select(['id', 'nickname', 'headimgurl', \DB::raw('max(day) as day')])->where(function($query){
            $appid = $this->request->input('appid') ?: $this->user()->switchToMiniUser()->last_appid ?: false;
            if($appid){
                $query->where('last_appid', $appid);
            }
        })->where('day', '>', 0)->withCount(['to_likes AS like_count' => function ($query) {
            $query->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_day');
        }, 'to_likes AS liked_count' => function ($query) {
            $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'sign_day');
        }])->groupBy('unionid')->offset($num)->limit(20)->orderBy('day', 'desc')->orderBy('id', 'asc')->get();
        $signDay['day'] = $user->day;

        $user_ids = User::where('day', '>', 0)->where(function($query){
            $appid = $this->request->input('appid') ?: $this->user()->switchToMiniUser()->last_appid ?: false;
            if($appid){
                $query->where('last_appid', $appid);
            }
        })->orderBy('day', 'desc')->orderBy('id', 'asc')->pluck('id')->toArray();
        $signDay['sort'] = in_array($user->id, $user_ids) ? array_search($user->id, $user_ids) : -1;
        return $signDay;
    }

    public function walk($num = 0)
    {
        $walk['sort'] = -1;
        $walk['list'] = UserWalk::select(['user_id', 'step_today as  step'])->whereIn('user_id', $this->getUserIds())->where('date', date('Y-m-d'))->with(['user' => function ($query) {
            $query->select('id', 'nickname', 'headimgurl')->withCount(['to_likes AS like_count' => function ($query) {
                $query->where('created_at', '>', date('Y-m-d'))->where('type', 'walk');
            }, 'to_likes AS liked_count' => function ($query) {
                $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'walk');
            }]);
        }])->offset($num)->limit(20)->orderBy('step', 'desc')->orderBy('id', 'asc')->get();

        $user_walk = UserWalk::select('user_id', 'step', 'date')->where('user_id', $this->user()->switchToMiniUser()->id)->where('date', date('Y-m-d'))->with(['user' => function ($query) {
            $query->select('id', 'nickname', 'headimgurl')->withCount(['to_likes AS like_count' => function ($query) {
                $query->where('created_at', '>', date('Y-m-d'))->where('type', 'walk');
            }, 'to_likes AS liked_count' => function ($query) {
                $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'walk');
            }]);
        }])->first();
        if ($user_walk) {
            $walk['sort'] = UserWalk::where('date', date('Y-m-d'))->whereIn('user_id', $this->getUserIds())->where('step', '>', $user_walk->step)->count();
        }
        $walk['walk'] = $user_walk;
        return $walk;
    }

    public function coin($num = 0)
    {
        $user = $this->user()->switchToMiniUser();
        $coin['list'] = User::select(['id', 'nickname', 'headimgurl', \DB::raw('max(coin) as coin')])->where(function($query){
            $appid = $this->request->input('appid') ?: $this->user()->switchToMiniUser()->last_appid ?: false;
            if($appid){
                $query->where('last_appid', $appid);
            }
            $query->where('coin', '>', 0)->where('is_mini_user',1);
        })->withCount(['to_likes AS like_count' => function ($query) {
            $query->where('created_at', '>', date('Y-m-d'))->where('type', 'coin');
        }, 'to_likes AS liked_count' => function ($query) {
            $query->where('user_id', $this->user()->switchToMiniUser()->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'coin');
        }])->groupBy('unionid')->offset($num)->limit(20)->orderBy('coin', 'desc')->orderBy('id', 'asc')->get();

        $coin['coin'] = $user->coin;
        $user_ids = User::where('coin', '>', 0)->where('is_mini_user',1)->where(function($query){
            $appid = $this->request->input('appid') ?: $this->user()->switchToMiniUser()->last_appid ?: false;
            if($appid){
                $query->where('last_appid', $appid);
            }
        })->orderBy('coin', 'desc')->orderBy('id', 'asc')->pluck('id')->toArray();
        $coin['sort'] = in_array($user->id, $user_ids) ? array_search($user->id, $user_ids) : -1;
        return $coin;
    }

    public function invite($num = 0)
    {
        $user = $this->user()->switchToMiniUser();
        $invite['list'] = UserInvite::select(['user_id', \DB::raw('COUNT(id) as invite_count')])
            ->whereIn('user_id', $this->getUserIds())
            ->where('created_at', '>', date('Y-m-d'))
            ->with(['user' => function ($query) use ($user) {
                $query->select('id', 'nickname', 'headimgurl')
                    ->withCount([
                        'to_likes AS like_count' => function ($query) {
                            $query->where('created_at', '>', date('Y-m-d'))->where('type', 'invite');
                        },
                        'to_likes AS liked_count' => function ($query) use($user) {
                            $query->where('user_id', $user->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'invite');
                        }
                        ]);
            }])
            ->groupBy('user_id')
            ->offset($num)
            ->limit(20)
            ->orderBy('invite_count', 'desc')
            ->orderBy('user_id', 'asc')
            ->get();
        $invite['invite'] = $user->invites()->where('created_at', '>', date('Y-m-d'))->count();
        $user_ids = UserInvite::select(['user_id', \DB::raw('COUNT(id) as invite_count')])
            ->whereIn('user_id', $this->getUserIds())
            ->where('created_at', '>', date('Y-m-d'))
            ->groupBy('user_id')
            ->orderBy('invite_count', 'desc')
            ->orderBy('user_id', 'asc')
            ->pluck('user_id')
            ->toArray();
        $invite['sort'] = in_array($user->id, $user_ids) ? array_search($user->id, $user_ids) : -1;
        return $invite;
    }

    public function like($to_user_id, $type)
    {
        $user = $this->user()->switchToMiniUser();
        $last = UserLike::where('user_id', $user->id)->where('to_user_id', $to_user_id)->where('type', $type)->where('created_at', '>', date('Y-m-d'))->first();
        if ($last != null) return $this->noContent();
        UserLike::create(['user_id' => $user->id, 'to_user_id' => $to_user_id, 'type' => $type]);
        $redis = app('redis');
        $like_times = $redis->incr('mornight:account:likeToday:'.$user->id);
        $redis->expire('mornight:account:likeToday:'.$user->id, untilTomorrow());
        $plus = $like_times > 3 ? 0 : 10;
        $remark = '排行榜点赞';
        if ($plus > 0) {
            changeCoin($user->id, $plus, 'like', $to_user_id, $remark);
            sendMsg($to_user_id, '互动消息提醒', 'like', $user->nickname . ' 为我点赞。');
        }
        return ['coin'=>$plus, 'diamond'=>0];
    }
}
