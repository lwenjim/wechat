<?php

namespace App\Http\Controllers;

use App\Models\UserSign;
use App\Models\UserSignAdd;
use App\Models\UserSignComment;
use App\Models\UserWalk;
use App\Models\User;
use App\Jobs\Sign;
use App\Models\Wechat;
use App\Models\WechatUser;
use App\Transformers\UserWalkTransformer;

class DayController extends Controller
{
    private $develomentUserId = 3484111111;

    public function calculateDiamond($uid = 0)
    {
        $uid = $uid ? $uid : $this->user()->switchToMiniUser()->id;
        $coin = calculateCoin($uid);
        $level = calculateLevel($coin);
        $diamond = calculateDiamond($level);
        return ['level' => $level, 'diamond' => $diamond];
    }

    public function sign()
    {
        $user = $this->user()->switchToMiniUser();
        if ($this->request->isMethod('post')) {
            $key_prefix_redis = $_SERVER['SERVER_NAME'] . ':' . str_replace('::', ':', __METHOD__) . ':';
            $ret = synchronousHandle($key_prefix_redis . 'sign-' . $user->id, function () {
                return $this->userSign();
            });
            if ($ret == 'insync') {
                return $this->errorBadRequest('正在处理中');
            }
            return $ret;
        } else {
            $year = $this->request->get('year', date('Y'));
            $month = $this->request->get('month', date('m'));
            $data['date'] = [];
            $days = date('t', strtotime("{$year}-{$month}-01"));
            $week = date('w', strtotime("{$year}-{$month}-01"));
            $signs = $user->signs()
                ->select('id', 'user_id', 'day', 'add', 'date', 'created_at')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get()
                ->groupBy('date')
                ->toArray();
            for ($i = 1 - $week; $i <= $days;) {
                for ($j = 0; $j < 7; $j++) {
                    if ($i <= $days && $i > 0) {
                        $i = str_pad($i, 2, "0", STR_PAD_LEFT);
                        $day = 0;
                        $add = 0;
                        $time = '';
                        $date = "{$year}-{$month}-{$i}";
                        if (isset($signs[$date])) {
                            $day = $signs[$date][0]['day'];
                            $add = $signs[$date][0]['add'];
                            $time = strtotime($signs[$date][0]['created_at']);
                        }
                        $data['date'][] = ['date' => $i, 'week' => $j, 'day' => $day, 'add' => $add, 'time' => $time];
                    }
                    $i++;
                }
            }
            $data['nickname'] = $user->nickname;
            $data['headimgurl'] = $user->headimgurl;
            $data['day'] = $user->day;
            $data['coin'] = $user->coin;
            $data['config'] = $user->config;
//            $data['count'] = UserSign::where('date', date('Y-m-d'))->count();
            $UserSign = UserSign::where('date', date('Y-m-d'))->select('user_id')->orderBy('created_at')->get()->toArray();
            $data['count'] = count($UserSign);
            $data['exceed'] = '0%';
            if ($data['count'] > 0) {
                foreach ($UserSign as $k => $sign) {
                    if ($sign['user_id'] == $user->id) {
                        $data['exceed'] = round((1 - ($data['count'] - $k) / $data['count']) * 100, 1) . '%';
                        break;
                    }
                }
            }
            $UserList = User::where('day', '>', 0)->select('id')->orderBy('day', 'DESC')->get()->toArray();
            $data['day_count'] = count($UserList);
            $data['day_exceed'] = '0%';
            if ($data['day_count'] > 0) {
                foreach ($UserList as $k => $sign) {
                    if ($sign['id'] == $user->id) {
                        $data['day_exceed'] = round((1 - ($data['day_count'] - $k) / $data['day_count']) * 100, 1) . '%';
                        break;
                    }
                }
            }
            if ($this->getSign($user) || in_array($user->id, [$this->develomentUserId])) {
                $data['status'] = 1;
            } else {
                $data['status'] = 3;
            }
            $data['order'] = -1;
            $sign = $user->signs()->where('date', date('Y-m-d'))->first();
            if ($sign != null && !in_array($user->id, [$this->develomentUserId])) {
                $data['order'] = UserSign::where('date', date('Y-m-d'))->where('created_at', '<', $sign->created_at)->count();
                $data['status'] = 2;
                $data['created_at'] = $sign->created_at->format('H:i:s');
            }
            $placeholder = json_decode(setting('moment_placeholder'), true);
            $data['moment_placeholder'] = isset($placeholder[date('Y-m-d')]) ? $placeholder[date('Y-m-d')] : $placeholder['default'];
            $data['user_id'] = $user;
            return $data;
        }
    }

    public function signAdd()
    {
        $user = $this->user()->switchToMiniUser();
        if ($this->request->isMethod('post')) {
            $date = $this->request->input('date');
            if ($date > date('Y-m-d')) return $this->errorBadRequest($date . '不能补签！');
            if ($date == date('Y-m-d') && date('H') < 5) return $this->errorBadRequest('还未到打卡时间不能补签！');
            if ($date != date('Y-m-d') && $user->signs()->where('date', date('Y-m-d'))->count() == 0) return $this->errorBadRequest('请先补签今天');
            if ($user->signs()->where('date', $date)->count()) return $this->errorBadRequest('已经补签过了');
            $trade_no = date('YmdHis') . mt_rand(1000, 9999);
            $add_num = $user->signs()->whereMonth('created_at', date('m'))->where('add', 1)->count();
            $signAdd = UserSignAdd::create(['user_id' => $user->id, 'user_ip' => $this->request->ip(), 'trade_no' => $trade_no, 'money' => pow(2, $add_num), 'date' => $date]);
            return $signAdd->id;
        } else {
            $sign = $user->signs()->select('day', 'add', 'date')->take(30)->orderBy('date', 'desc')->get();
            $data['list'] = $sign;
            $data['coin'] = $user->coin;
            $data['day'] = $user->day;
            if (date('H.i') > 10.3) {
                $data['date'] = date('Y-m-d');
            } else {
                $data['date'] = date('Y-m-d', strtotime('-1 day'));
            }
            return $data;
        }
    }

    public function walk()
    {
        $user = $this->user();
        $walk = $user->walks()->select('id', 'step', 'step_wx', 'step_today')->where('date', date('Y-m-d'))->first();
        if (!$walk) {
            $walk = $user->walks()->create(['date' => date('Y-m-d')]);
        }
        if ($this->request->isMethod('post')) {
            $step = $this->request->input('step');
            if (!empty($step) && $step > $walk->step_wx) {
                $intervalStep = $walk->step + $step - $walk->step_wx;
                $walk->update(['step' => $intervalStep, 'step_wx' => $step, 'user_ip' => $this->request->ip(), 'step_today' => $intervalStep]);
                UserWalk::cacheFlush();
            }
            return $this->created();
        } elseif ($this->request->isMethod('put')) {
            if (date('G') < 18) return $this->errorBadRequest('每日兑换时间为18：00：00 ~ 23：59：59');
            $step = $this->request->input('step');
            if (!$step || $step > $walk->step) $step = $walk->step;
            if ($step >= 6880) {
                $plus = intval($step / 100);
            } else {
                $plus = intval($step / 200);
            }
            if ($plus > 0) {
                $plus = $plus > 200 ? 200 : $plus;
                sendMsg($user->id, '原力消息提醒', 'walk', '在【夕】中兑换了步数，获得了' . $plus . '原力');
                changeCoin($user->id, $plus, 'walk', $walk->id, '兑换步数获得原力');
                $walk->decrement('step', $step);
                sendMsg($user->id, '原力消息提醒', 'walk-award', '兑换步数奖励90原力');
                changeCoin($user->id, 90, 'walk-award', $walk->id, '兑换步数奖励原力');
            }
            if (!$user->fisherMission()->where(['step_exchange' => 1])->exists()) {
                $user->fisherMission()->update(['step_exchange' => 1]);
                incrementBlueDiamond(9999, $user->id, 5, '第一次打卡赠送5个蓝钻');
            }
            return $plus;
        } else {
            $year = $this->request->get('year', date('Y'));
            $month = $this->request->get('month', date('m'));
            $data['date'] = [];
            $days = date('t', strtotime("{$year}-{$month}-01"));
            $week = date('w', strtotime("{$year}-{$month}-01"));
            $signs = $user->walks()->select('id', 'user_id', 'date', 'step', 'updated_at')->whereYear('updated_at', $year)->whereMonth('updated_at', $month)->get()->groupBy('date')->toArray();
            for ($i = 1 - $week; $i <= $days;) {
                for ($j = 0; $j < 7; $j++) {
                    if ($i <= $days && $i > 0) {
                        $i = str_pad($i, 2, "0", STR_PAD_LEFT);
                        $time = '';
                        $step = 0;
                        $date = "{$year}-{$month}-{$i}";
                        if (isset($signs[$date])) {
                            $time = strtotime($signs[$date][0]['updated_at']);
                            $step = $signs[$date][0]['step'];
                        }
                        $data['date'][] = ['date' => $i, 'step' => $step, 'week' => $j, 'time' => $time];
                    }
                    $i++;
                }
            }
            $userCoin = $user->coins()->where(['action' => 'walk'])->where('created_at', '>', date('Y-m-d'))->value('number');
            $data['nickname'] = $user->nickname;
            $data['headimgurl'] = $user->headimgurl;
            $data['walked'] = $userCoin ? 1 : 0;
            $data['step'] = $walk->step;
            $data['coin'] = $userCoin;
            $data['step_today'] = $walk->step_today;
            $data['order'] = UserWalk::where('date', date('Y-m-d'))->where('step', '>=', $walk->step)->count();
            return $data;
        }
    }

    function weather($longitude, $latitude)
    {
        $weather = new \App\Services\Weather\Weather();
        try {
            $response = $weather->getWeather($longitude, $latitude);
            $json_result = json_decode($response->getBody(), true);
            $result = $json_result['showapi_res_body'];
            $return['currentTempture'] = $result['now']['temperature'];
            $return['precipitation'] = $result['now']['weather'];
            $return['temperaturedesc'] = $result['now']['weather'];
            $return['temperaturemax'] = $result['f1']['day_air_temperature'];
            //$return['temperaturemin'] = $result['f1']['night_air_temperature'];
            $return['pm25'] = $result['now']['aqiDetail']['pm2_5'];
            $return['3hourforcast'] = $result['f1']['3hourForcast'];
            $jsonResult = $this->http("http://api.map.baidu.com/geocoder/v2/?output=json&ak=iplFs3MS2smrZEyS1uYvw0VPSzvH0uPA&coordtype=wgs84ll&location=" . $latitude . "," . $longitude . "");
            $return['address'] = $jsonResult['result']['addressComponent']['city'] . $jsonResult['result']['addressComponent']['district'] . $jsonResult['result']['addressComponent']['street'];
            return $return;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function moment()
    {
        $redis = app('redis');
        $data = $this->request->input();
        $user = $this->user()->switchToMiniUser();

        $lastAppid = !empty($data['appid']) ? $data['appid'] : $user->last_appid ? $user->last_appid : config('wechat.app_id');
        $user_read_article_num = 'mornight:account:likeMessage:' . $user->id;
        $user_behave_lock = 'mornight:account:likeMessage:lock:' . $user->id;
        $wechatId = Wechat::where(['appid' => $lastAppid])->value("id");
        if ($this->request->isMethod('post')) {
            if ($redis->exists($user_behave_lock)) return $this->errorBadRequest('评论过于频繁！');
            if (!isset($data['content']) || empty($data['content'])) return $this->errorBadRequest('您发布的内容不能为空！');
            if ($moment_sensitive = setting('moment_sensitive'))
                foreach (explode(',', strtolower($moment_sensitive)) as $value)
                    if (strpos(strtolower($data['content']), $value) !== false)
                        return $this->errorBadRequest('您发布的内容含有非法词汇！请检查后重试。');
            $comment['nickname'] = $this->user()->nickname;
            $comment['headimgurl'] = $this->user()->headimgurl;
            $comment['content'] = $data['content'];
            $comment['location'] = $data['location'];
            $comment['wechat_id'] = $wechatId;
            $comment['time'] = date('H:i:s');
            $comment['date'] = date('Y-m-d');

            $redis->setex($user_behave_lock, 60, 1);
            $user->userSignComment()->create($comment);
            if (!$redis->exists($user_read_article_num)) $redis->expire($user_read_article_num, untilTomorrow());
            if (!$redis->exists($user_read_article_num)) {
                $number = 30;
                changeCoin($user->id, $number, 'moment', 0, '互动留言送原力');
                $redis->incr($user_read_article_num);
            } else {
                $number = 0;
            }
            $redis->del($user_behave_lock);
            return ['coin' => $number, 'diamond' => fisherMissionAward($wechatId, $user->id, 'message')];
        } else {
            return UserSignComment::where(['wechat_id' => $wechatId, 'date' => date('Y-m-d')])->withCount(['to_likes AS like_count' => function ($query) {
                $query->where('created_at', '>', date('Y-m-d'));
            }, 'to_likes AS liked_count' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('created_at', '>', date('Y-m-d'))->where('type', 'UserSignComment');
            }])->orderBy('like_count', 'desc')->orderBy('id', 'desc')->paginate(isset($data['row']) ? $data['row'] : 20, ['*'], 'page', isset($data['start']) ? $data['start'] : 1);
        }
    }
    public function voice()
    {
        if ($this->request->hasFile('voice')) {
            $voice = $this->request->file('voice');
            if ($voice->isValid()) {
                shell_exec('/usr/bin/ffmpeg -y  -i ' . $voice->path() . '  -acodec pcm_s16le -f s16le -ac 1 -ar 16000 ' . $voice->path());
                return \App\Services\Voice::post(file_get_contents($voice->path()));
            }
        }
        return $this->noContent();
    }

    public function like()
    {
        $redis = app('redis');
        if ($this->request->isMethod('post')) {
            $number = $this->request->input('number', 1);
            $sum = $this->user()->switchToMiniUser()->coins()->where('action', 'good')->where('created_at', '>', date('Y-m-d'))->sum('number');
            if ($sum < 10) {
                $remain = 10 - $sum;
                if ($number >= 10) {
                    $plus = $remain;
                } else {
                    if ($number >= $remain) {
                        $plus = $remain;
                    } else {
                        $plus = $number;
                    }
                }
                if ($plus > 0) changeCoin($this->user()->switchToMiniUser()->id, $plus, 'good', 0, '互动点赞送原力');
            }
            $redis->hincrby('mornight:daylike', date('Y-m-d'), $number);
        }
        return intval($redis->hget('mornight:daylike', date('Y-m-d')));
    }

    public function webLike()
    {
        $redis = app('redis');
        $self_key = 'morning:daylike-user:uid=' . \Auth::user()->id;
        if ($this->request->isMethod('post')) {
            $number = $this->request->input('number', 1);
            $sum = $this->user()->switchToMiniUser()->coins()->where('action', 'good')->where('created_at', '>', date('Y-m-d'))->sum('number');
            if ($sum < 10) {
                $remain = 10 - $sum;
                if ($number >= 10) {
                    $plus = $remain;
                } else {
                    if ($number >= $remain) {
                        $plus = $remain;
                    } else {
                        $plus = $number;
                    }
                }
                if ($plus > 0) changeCoin($this->user()->switchToMiniUser()->id, $plus, 'good', 0, '互动点赞送原力');
            }
            $redis->hincrby('mornight:daylike', date('Y-m-d'), $number);
            if (!$redis->exists($self_key)) {
                $redis->incr($self_key);
                $expire = strtotime(date('Y-m-d 23:59:59')) - time();
                $redis->expire($self_key, $expire);
            } else {
                $redis->incr($self_key);
            }
        }
        return ['total' => intval($redis->hget('mornight:daylike', date('Y-m-d'))), 'self' => $redis->get($self_key)];
    }

    public function give($user_id)
    {
        $key_prefix_redis = $_SERVER['SERVER_NAME'] . ':' . str_replace('::', ':', __METHOD__) . ':';
        $number = 2000;
        $user = User::select('id', 'nickname', 'headimgurl')->find($user_id);
        $walk = $user->walks()->select('id', 'step')->where('date', date('Y-m-d'))->first();
        $loginUser = $this->user()->switchToMiniUser();
        $my_walk = $loginUser->walks()->select('id', 'step')->where('date', date('Y-m-d'))->first();
        if (!$my_walk || !$walk) return $this->errorBadRequest('您还没有步数哦');
        if ($this->request->isMethod('post')) {
            if ($user_id == $loginUser->id) return $this->errorBadRequest('不能给自己赠送原力哦');
            if ($number > $my_walk->step) return $this->errorBadRequest('原力不够哦');
            $redis = app('redis');

            $key_redis_walk_receive = $key_prefix_redis . date('Y-m-d:') . $user->id;
            if (!$redis->exists($key_redis_walk_receive)) $redis->expire($key_redis_walk_receive, remainSecondsForToday());
            if ($redis->get($key_redis_walk_receive) > 5) return $this->errorBadRequest('该用户已经不能再接受捐赠啦！');

            $key_redis_walk_send = $key_prefix_redis . date('Y-m-d:') . $loginUser->id;
            if (!$redis->exists($key_redis_walk_send)) $redis->expire($key_redis_walk_send, remainSecondsForToday());
            if ($redis->get($key_redis_walk_send) > 5) return $this->errorBadRequest('您当日的捐赠次数已经用完啦！');

            $key_redis_to_somebody = $key_prefix_redis . $loginUser->id . '-' . $user->id;
            if ($redis->exists($key_redis_to_somebody)) return $this->errorBadRequest('今天你已经捐赠过不能再捐啦！');
            $redis->setex($key_redis_to_somebody, remainSecondsForToday(), date('Y-m-d H:i:s'));

            if ($loginUser->msgs()->where('type', 'walk')->where('created_at', '>', date('Y-m-d'))->count() > 0) return $this->errorBadRequest('您的好友已经完成兑换！');

            $my_walk->decrement('step', $number);
            $walk->increment('step', $number);
            \App\Models\UserWalkGive::create(['user_walk_id' => $my_walk->id, 'to_user_walk_id' => $walk->id, 'number' => $number]);
            $redis->incr($key_redis_walk_receive);
            $redis->incr($key_redis_walk_send);
            return $this->created();
        }
        $data['nickname'] = $user->nickname;
        $data['headimgurl'] = $user->headimgurl;
        $data['number'] = $number;
        $data['step'] = $my_walk->step;
        $data['id'] = $user->id;
        return $data;
    }

    public function gives()
    {
        $walk = $this->user()->switchToMiniUser()->walks()->select('id', 'user_id', 'step')->where('date', date('Y-m-d'))->first();
        if ($walk) {
            return $this->item($walk, new UserWalkTransformer());
        }
        return $this->noContent();
    }

    public function invite($user_id)
    {
        $user = User::select('id', 'nickname', 'headimgurl', 'day', 'coin')->findOrFail($user_id);
        $data['nickname'] = $user->nickname;
        $data['headimgurl'] = $user->headimgurl;
        $data['day'] = $user->day;
        $data['coin'] = $user->coin;
        $sort = -1;
        $sign = $user->signs()->where('date', date('Y-m-d'))->first();
        if ($sign) {
            $sort = UserSign::where('date', date('Y-m-d'))->where('created_at', '<', $sign->created_at)->count();
            $data['created_at'] = $sign->created_at->format('H:i:s');
        }
        $data['sort'] = $sort;
        $data['count'] = User::where('status', '>', 0)->count();
        return $data;
    }

    public function qrcode($user_id)
    {
        $cache = app('cache');
        $qrcodekey = 'appcode:' . $user_id;
        if ($cache->has($qrcodekey)) {
            $qrcodeurl = $cache->get($qrcodekey);
        } else {
            $page = $this->request->get('page', 'pages/index/index');
            $width = $this->request->get('width', 430);
            $color = $this->request->get('color', '0,0,0');
            $qrcodeurl = getAppCode($user_id, $page, $width, $color);
            $cache->forever($qrcodekey, $qrcodeurl);
        };
        return $qrcodeurl;
    }

    function getRand()
    {
        $result = '';
        $proArr = [1 => 4, 2 => 3, 3 => 1, 4 => 1, 5 => 1];
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        return $result;
    }

    function getSign($user)
    {
        $now_hour = date('H');
        $now_week = date('w');
        //新用户redis 48小时内打卡特权
        if (app('redis')->get('mornight:account:fisher:' . $user->id)) {
            return true;
        }
        return ($now_week >= 1 && $now_week <= 5 && $now_hour >= 5 && $now_hour < 10) ||
            (($now_week == 0 || $now_week == 6 || date('Y-m-d') >= '2018-10-01' && date('Y-m-d') <= '2018-10-07') && $now_hour >= 5 && $now_hour <= 12);
    }

    function resign()
    {
        $key_prefix_redis = $_SERVER['SERVER_NAME'] . ':' . str_replace('::', ':', __METHOD__) . ':';
        $ret = synchronousHandle($key_prefix_redis . 'sign-' . $this->user()->switchToMiniUser()->id, function () {
            return $this->resign_do();
        });
        if ($ret == 'insync') {
            return $this->errorBadRequest('正在处理中');
        }
        return $ret;
    }

    private function resign_do()
    {
        $user = $this->user()->switchToMiniUser();
        $date = $this->request->input('date');
        $to_user_id = $this->request->input('to_user_id');
        $to_user_info = User::where('id', $to_user_id)->first();
        $getHelpUserList = "mornight:resign:getHelpUserList:" . date('Y-m') . ":{$to_user_id}";
        $getHelpUserIdAndDate = "mornight:resign:getHelpUserIdAndDate:" . date('Y-m') . ":{$to_user_id}";
        $toHelpUserList = "mornight:resign:toHelpUserList:" . date('Y-m') . ":" . $user->id;

        $redis = app('redis');
        $data = [];
        $data['user_id'] = $to_user_id;
        $data['user_ip'] = $this->request->ip();
        $data['date'] = $date;
        $data['created_at'] = $date . ' ' . date('H:i:s');
        $data['resign_user_id'] = $user->id;
        $data['add'] = 1;
        $data['day'] = User::find($to_user_id)->getDays($date);
        $data['hour'] = date('H');
        $data['appid'] = $user->last_appid ? $user->last_appid : config('wechat.app_id');
        $data['wechat_id'] = !empty($data['appid']) ? Wechat::where(['appid' => $data['appid']])->value('id') : 1;

        if (!$redis->exists($getHelpUserList)) $redis->expire($getHelpUserList, strtotime(date('Y-m-d', strtotime('+1 month'))));
        if (!$redis->exists($toHelpUserList)) $redis->expire($toHelpUserList, strtotime(date('Y-m-d', strtotime('+1 month'))));
        if ($user->id == $to_user_id) return $this->errorBadRequest('不能给自己补卡');
        if ($redis->sismember($getHelpUserList, $user->id)) return $this->errorBadRequest($to_user_info->nickname . ' 的补卡已完成，请勿重复补卡。');
        if (UserSign::where(['date' => $date, 'user_id' => $to_user_id])->exists()) return $this->errorBadRequest('已经打过卡');
        if ($redis->scard($getHelpUserList) >= 3) return $this->errorBadRequest('你的好友' . $to_user_info->nickname . '本月已经有3个好友帮助补卡');
        if ($redis->scard($toHelpUserList) >= 3) return $this->errorBadRequest('本月已经帮助3个好友补过卡，请下个月再尝试补卡。');

        UserSign::create($data);
        updateUserSignDay($user);
        $redis->sadd($getHelpUserList, $user->id);
        $redis->sadd($getHelpUserIdAndDate, $user->id . '|' . $date);
        $redis->sadd($toHelpUserList, $to_user_id);
        return $this->error('已成功帮助' . $to_user_info->nickname . '补卡，补卡日期为 ' . $date, 200);
    }

    function getHelpList()
    {
        $redis_key = "mornight:resign:date:" . date('Y-m') . ":" . $this->user()->switchToMiniUser()->id;
        $redis = app('redis');
        $list = $redis->smembers($redis_key);
        if (empty($list)) return [];
        $ids = [];
        $list = array_map(function ($row) use (&$ids) {
            list($user_id, $date) = explode('|', $row);
            $ids[] = $user_id;
            return ['user_id' => $user_id, 'resign_date' => $date];
        }, $list);
        $users = User::whereIn('id', $ids)->get();
        foreach ($list as $key => $row) {
            foreach ($users as $user) {
                if ($row['user_id'] != $user->id) continue;
                $list[$key]['user_info'] = $user;
            }
        }
        return $list;
    }

    private function userSign()
    {
        $miniUser = $this->user()->switchToMiniUser();
        $key_prefix_redis = $_SERVER['SERVER_NAME'] . ':' . str_replace('::', ':', __METHOD__) . ':';
        $key_redis_lock = $key_prefix_redis . 'lock:' . $miniUser->id;
        $city = $this->request->input('city', '神秘的地方');

        $data['user_id'] = $miniUser->id;
        $data['user_ip'] = $this->request->ip();
        $data['date'] = date('Y-m-d');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['hour'] = date('H');
        $data['appid'] = $this->getLastAppid();
        $data['wechat_id'] = !empty($data['appid']) ? Wechat::where(['appid' => $data['appid']])->value('id') : 1;
        $data['day'] = $miniUser->getDays();

        $this->bindGzhToMini($miniUser);
        if (!in_array($miniUser->id, [$this->develomentUserId]) && !$this->getSign($miniUser)) return $this->errorBadRequest('不能打卡');

        $orderCount = $miniUser->getOrderCount();
        if (!in_array($miniUser->id, [$this->develomentUserId]) && $orderCount > 0) return $this->errorBadRequest('已经打卡过了');

        $plus = getDakaYuanli_v2($data['day']);
        $msg = "通过早起打卡获得 {$plus} 原力";
        $redis = app('redis');
        if ($redis->exists($key_redis_lock)) return $this->errorBadRequest('正在处理中');

        $redis->set($key_redis_lock, 3600, 1);
        $usersign = UserSign::create($data);
        $redis->del($key_redis_lock);

        $fisherMission = fisherMission($miniUser->id);
        $award_diamond = $fisherMission->sign == 0 ? 8 : 0;
        $diamond = $this->calculateDiamond($miniUser->id);

        dispatch((new Sign($this->user()->id, $miniUser, $usersign, $plus, $diamond['diamond'], $msg, $city))->onQueue('sign'));

        $result['coin'] = $plus;
        $result['diamond'] = $diamond['diamond'] + $award_diamond;
        $result['order'] = $orderCount;
        $result['day'] = $data['day'];
        $result['time'] = date('Y-m-d H:i:s');
        $result['percent'] = $this->countBeyond();
        return $result;
    }

    private function countBeyond()
    {
        $total = UserSign::where('date', date('Y-m-d'))->count();
        $yesterdayTotal = UserSign::where('date', date('Y-m-d', strtotime('-1 day')))->count();
        $yesterdayTotal = $yesterdayTotal == 0 ? 1 : $yesterdayTotal;
        $percent = $total / $yesterdayTotal;
        if ($percent == 0) {
            return 99;
        } elseif ($percent > 1) {
            return 1;
        } elseif ($percent < 1) {
            return 100 - floor($percent * 100);
        }
    }
}
