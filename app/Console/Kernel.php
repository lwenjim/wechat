<?php

namespace App\Console;

use App\Models\User;
use function count;
use const false;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Laravelista\LumenVendorPublish\VendorPublishCommand;
use DB;
use Mockery\Exception;
use function print_r;


class Kernel extends ConsoleKernel
{
    protected $commands = [
        VendorPublishCommand::class,
        Commands\ProcessServer::class,
        Commands\WebSocketServer::class,
        Commands\HttpServer::class,
        Commands\TestServer::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            static::remainMorningUp();
        })->everyThirtyMinutes()->between('5:00', '9:30');

        $schedule->call(function () {
            static::stepExchange();
        })->everyThirtyMinutes()->between('18:00', '22:00');

        $schedule->call(function () {
            static::remianEveningRead();
        })->everyThirtyMinutes()->between('18:00', '22:30');

        $schedule->call(function () {
            static::currentTask();
        })->daily();

        $schedule->call(function () {
            static::updatePortraitRegionStatistic();
        })->at('2:50');

        $schedule->call(function () {
            static::protectSqlSizeLimit();
        })->everyFiveMinutes();

        $schedule->call(function () {
            static::remainMornightUpDayLast();
        })->at('9:50');

        $schedule->call(function () {
            static::syncStaffSendLog2Db();
        })->everyFiveMinutes();

        $schedule->call(function () {
            static::userAnalyse();
            static::WechatStats();
        })->everyFifteenMinutes();

        $schedule->call(function () {
            static::refresh21DayInnerCoin();
        })->everyThirtyMinutes()->between('1:15', '2:15');

    }

    private static function refresh21DayInnerCoin()
    {
        set_time_limit(0);
        foreach (getEnableGzh() as $key => $wechat) {
            foreach (array_chunk(getActiveUser($wechat->appid), 500, true) as $openids) {
                foreach ($openids as $openid) {
                    $gzhUser = User::where(['openid' => $openid])->first();
                    if (empty($gzhUser) || $gzhUser->mini_user_id <= 0) continue;
                    $middleUser = $gzhUser->switchToMiniUser();
                    if ($middleUser->is_mini_user != 1) continue;
                    $totalCoin = \App\Models\UserCoin::where('user_id', $middleUser->id)->where('created_at', '>', date('Y-m-d', strtotime('-21 day')))->where('created_at', '<', date('Y-m-d'))->sum('number');
                    $middleUser->update(['coin' => $totalCoin]);
                    usleep(rand(10, 200));
                }
            }
            usleep(rand(10, 200));
        }
    }

    public static function userAnalyse()
    {
        set_time_limit(0);
        $redis = app('redis');
        foreach (getEnableGzh() as $key => $wechat) {
            try {
                $wechatPlatforms = getApp($wechat->appid)->user->lists();
            } catch (\Exception $exception) {
                info($wechat->appid . "\tuserAnalyse\n" . $exception->getMessage());
                if (strpos($exception->getMessage(), 'user limited hint') !== false) $wechat->update(['status' => 0]);
            }
            $lastDayData = \App\Models\WechatStats::where('appid', $wechat->appid)->where('date', date('Y-m-d', strtotime("-1 days")))->first();
            $data = [];
            $data['count'] = $wechatPlatforms->total;
            $data['invite'] = \App\Models\UserInvite::where('appid', $wechat->appid)->where('created_at', '>', date('Y-m-d'))->count();
            $data['sign'] = \App\Models\UserSign::where(['appid' => $wechat->appid])->where('date', date('Y-m-d'))->count();
            $data['active'] = count($redis->keys('mornight:active:' . $wechat->appid . ':*'));
            $data['grow'] = is_null($lastDayData) ? $wechatPlatforms->total : ($wechatPlatforms->total - $lastDayData->count);
            $data['analyse'] = \GuzzleHttp\json_encode($data);
            \App\Models\WechatStats::updateOrCreate(['date' => date('Y-m-d'), 'appid' => $wechat->appid], $data);
            $redis->zadd(config('config.RedisKey.0'), $wechatPlatforms->total, $wechat->id);
            usleep(rand(10, 200));
        }
    }

    private static function WechatStats()
    {
        $redis = app('redis');
        $sign_total = \App\Models\UserSign::where('date', date('Y-m-d'))->count();
        $user_total = array_sum($redis->zRange(config('config.RedisKey.0'), 0, -1, true));
        $redis->set(config('config.RedisKey.1'), $sign_total);
        $redis->set(config('config.RedisKey.2'), $user_total);
        \App\Models\WechatStatsAdmin::updateorcreate(['date' => date('Y-m-d')], ['user_total' => $user_total, 'sign_total' => $sign_total]);
    }

    private static function syncStaffSendLog2Db()
    {
        $redisKey = config('database.redis.keys.0');
        $redis = app('redis');
        $logs = [];
        for ($i = 0; $i < 500; $i++) {
            $log = $redis->rpop($redisKey);
            if (empty($log)) {
                break;
            }
            $logs[] = \GuzzleHttp\json_decode($log, true);
        }
        if (empty($logs)) return;
        DB::table('user_staff_send_log')->insert($logs);
    }

    private static function remianEveningRead()
    {
        set_time_limit(0);
        $time = date('H:i');
        if (!in_array($time, ['18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00', '22:30'])) return;
        static::protectSqlSizeLimit();
        $ret = [];
        foreach (getEnableGzh() as $wechat) {
            if (!static::existGzhEntry($wechat->appid)) continue;
            $active_user = getActiveUser($wechat->appid);
            $reply = static::getWechatReplay($wechat->id, '公众号授权第三方平台-晚读提醒');
            $wechat->users()->wherePivot('is_default', 1)
                ->wherePivot('subscribe', 1)
                ->wherePivotIn('openid', $active_user)
                //->whereIn('nickname', ['Jim', 'Annie蔡', '王聪', "'3.1415925", '看你肯不肯(吴诚)', '灿灿'])
                ->select('id', 'nickname', 'mini_user_id', 'config')
                ->chunk(1000, function ($gzhs) use ($reply, $wechat, $time, &$ret) {
                    if ($gzhs->isEmpty() || empty($reply) || empty($reply->content)) return;
                    $standarduptime = 'mornight:remianEveningRead:standarduptime';
                    $redis = app('redis');
                    if (!$redis->exists($standarduptime)) $redis->expire($standarduptime, untilTomorrow());
                    foreach ($gzhs as $index => $gzh) {
                        if ($gzh->mini_user_id > 0) {
                            $mini_program = $gzh->switchToMiniUser();
                            $remain_evening_read_time = empty($mini_program->config['remain_evening_read_time']) ? '20:00' : $mini_program->config['remain_evening_read_time'];
                            $remain_evening_read = empty($mini_program->config['remain_evening_read']) ? 1 : $mini_program->config['remain_evening_read'];
                            if ($remain_evening_read != 1
                                || empty($mini_program->config['remind'])
                                || $mini_program->config['remind'] != $wechat->id
                                || $remain_evening_read_time != $time
                            ) {
                                if ($time == '20:00') $redis->lpush($standarduptime, $gzh->id . '|1|' . $mini_program->config['remind'] . '-' . $wechat->id);
                                continue;
                            }
                        } else {
                            $remain_evening_read_time = empty($gzh->config['remain_evening_read_time']) ? '20:00' : $gzh->config['remain_evening_read_time'];
                            if ($remain_evening_read_time != $time) {
                                if ($time == '20:00') $redis->lpush($standarduptime, $gzh->id . '|2|' . $remain_evening_read_time . '-' . $time);
                                continue;
                            }
                        }
                        $gzh->openid = $gzh->pivot->openid;
                        $content = \GuzzleHttp\json_decode($reply->content);
                        $content->url = ['https://www.cxyun.com/h5/#/index/1'];
                        $content = \GuzzleHttp\json_encode($content);
                        dispatch((new \App\Jobs\EveningReadRemind($gzh, $content, $wechat->appid, $reply->reply))->onQueue('remind'));
                        $ret[] = $wechat->name . '-' . $gzh->nickname;
                        usleep(rand(10, 1000));
                    }
                });
        }
        return $ret;
    }

    private static function remainMornightUpDayLast()
    {
        set_time_limit(0);
        $ret = [];
        $mini_sign_user_ids = \App\Models\UserSign::where('date', date('Y-m-d'))->pluck('user_id')->toArray();
        static::protectSqlSizeLimit();
        foreach (getEnableGzh() as $wechat) {
            if (!static::existGzhEntry($wechat->appid)) continue;
            $active_user = getActiveUser($wechat->appid);
            $wechat->users()->wherePivot('is_default', 1)
                ->wherePivot('subscribe', 1)
                ->wherePivotIn('openid', $active_user)
                ->select('id', 'nickname', 'mini_user_id', 'config')
                ->chunk(1000, function ($gzhs) use ($mini_sign_user_ids, $wechat, &$ret) {
                    if ($gzhs->isEmpty()) return;
                    foreach ($gzhs as $index => $gzh) {
                        if ($gzh->mini_user_id > 0) {
                            $mini_program = $gzh->switchToMiniUser();
                            if (in_array($mini_program->id, $mini_sign_user_ids)
                                || empty($mini_program->config['sign'])
                                || $mini_program->config['sign'] != 1
                                || empty($mini_program->config['remind'])
                                || $mini_program->config['remind'] != $wechat->id
                            ) {
                                continue;
                            }
                        } else {
                            if ($gzh->config['sign'] != 1) {
                                continue;
                            }
                        }
                        $lastRemind = new \App\Jobs\RemainMornightUpDayLast($wechat->appid, $gzh->pivot->openid, '=￣ω￣=还有10分钟就停止打卡啦！今天是不是忘记了！快去！');
                        dispatch($lastRemind->onQueue('remind'));
                        $ret[] = $wechat->name . '-' . $gzh->nickname;
                        usleep(rand(10, 1000));
                    }
                });
        }
        return $ret;
    }

    public static function protectSqlSizeLimit()
    {
        $max_allowed_packet = DB::select("show variables like 'max_allowed_packet'")[0]->Value;
        if ($max_allowed_packet <= 1073741800) {
            DB::connection()->getPdo()->exec("set global max_allowed_packet = 1073741824");
            alarm('max_allowed_packet is reseted,current value:' . $max_allowed_packet);
        }
    }

    public static function existGzhEntry($appid)
    {
        try {
            $menu = getApp($appid)->menu->current();
            return stripos(print_r($menu->toArray(), 1), 'chenxishike') > -1;
        } catch (\Exception $exception) {
            info("existGzhEntry\n" . $exception->getMessage());
        }
        return false;
    }

    public static function getWechatReplay($wechat_id, $filter = '公众号授权第三方平台-早起提醒')
    {
        $replys = \App\Models\WeChatReply::whereIn('wechat_id', [$wechat_id, 1])
            ->where(['receive' => 'keyword', 'status' => 1, 'keyword' => $filter])
            ->select('content', 'reply', 'wechat_id')
            ->get();
        if (empty($replys)) return false;
        if (count($replys) == 1) return $replys[0];
        foreach ($replys as $reply) {
            if ($reply->wechat_id == $wechat_id) return $reply;
        }
    }

    private static function remainMorningUp()
    {
        set_time_limit(0);
        $time = date('H:i');
        if (!in_array($time, ['05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30'])) return;
        $mini_sign_user_ids = \App\Models\UserSign::where('date', date('Y-m-d'))->pluck('user_id')->toArray();
        static::protectSqlSizeLimit();
        $ret = [];
        foreach (getEnableGzh() as $wechat) {
            if (!static::existGzhEntry($wechat->appid)) continue;
            $active_user = getActiveUser($wechat->appid);
            $reply = static::getWechatReplay($wechat->id);
            $wechat->users()->wherePivot('is_default', 1)
                ->wherePivot('subscribe', 1)
                ->wherePivotIn('openid', $active_user)
                ->select('id', 'nickname', 'mini_user_id', 'config')
                ->chunk(500, function ($gzhs) use ($reply, $mini_sign_user_ids, $wechat, $time, &$ret) {
                    if ($gzhs->isEmpty() || empty($reply) || empty($reply->content)) return;
                    foreach ($gzhs as $index => $gzh) {
                        if ($gzh->mini_user_id > 0) {
                            $mini_program = $gzh->switchToMiniUser();
                            if (in_array($mini_program->id, $mini_sign_user_ids)
                                || empty($mini_program->config['sign'])
                                || $mini_program->config['sign'] != 1
                                || empty($mini_program->config['remind'])
                                || $mini_program->config['remind'] != $wechat->id
                                || empty($mini_program->config['sign_time'])
                                || $mini_program->config['sign_time'] != $time) {
                                continue;
                            }
                        } else {
                            if ($gzh->config['sign_time'] != $time) {
                                continue;
                            }
                        }
                        $gzh->openid = $gzh->pivot->openid;
                        dispatch((new \App\Jobs\SignRemind($gzh, $reply->content, $wechat->appid, $reply->reply))->onQueue('remind'));
                        $ret[] = $wechat->name . '-' . $gzh->nickname;
                        usleep(rand(10, 1000));
                    }
                });
        }
        return $ret;
    }

    private static function stepExchange()
    {
        set_time_limit(0);
        $time = date('H:i');
        if (!in_array($time, ['18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00'])) return;
        $walk_array = \App\Models\UserCoin::where('action', 'walk')
            ->where('created_at', '>', date('Y-m-d'))
            ->pluck('user_id')
            ->toArray();
        User::select('id', 'openid', 'nickname', 'day')
            ->where(['config->walk' => 1, 'is_mini_user' => 1, 'config->walk_time' => $time])
            ->chunk(1000, function ($openid) use ($walk_array) {
                if ($openid->isEmpty()) return;
                foreach ($openid as $val) {
                    if (in_array($val->id, $walk_array)) continue;
                    $data = [
                        'keyword1' => '运动兑换原力时间到，快来兑换啦！',
                        'keyword2' => '18:00-22:00',
                        'keyword3' => $val->day
                    ];
                    $TplMsg = new \App\Jobs\TplMsg('X7NswV7zCPAJI0hr36SpnVp3zVX6Uu_HthqD3ufp0Xg', 'pages/index/index', $data, $val->openid, 2);
                    dispatch($TplMsg->onQueue('remind'));
                    usleep(rand(10, 100));
                }
            });
    }

    private static function currentTask()
    {
        $sign_user_id = \App\Models\UserSign::where('date', date('Y-m-d', strtotime('-1 day')))->select('user_id')->pluck('user_id');
        User::whereNotIn('id', $sign_user_id)->where(['is_mini_user' => 1])->where('day', '>', 0)->update(['day' => 0]);

        $cards_dir = storage_path('cards');
        $cards_images = scandir($cards_dir);
        foreach ($cards_images as $v) {
            $file = $cards_dir . '/' . $v;
            if (is_file($file) && filemtime($file) < strtotime(date('Y-m-d'))) {
                unlink($file);
            }
        }
        $achieves_dir = storage_path('achieves');
        $achieves_images = scandir($achieves_dir);
        foreach ($achieves_images as $v) {
            $file = $achieves_dir . '/' . $v;
            if (is_file($file) && filemtime($file) < strtotime(date('Y-m-d'))) {
                unlink($file);
            }
        }
    }

    public static function updatePortraitRegionStatistic()
    {
        getEnableGzh()->each(function ($wechat) {
            $sql = "SELECT u.sex,COUNT(1) cnt FROM wechat_user wu LEFT JOIN `user` u ON wu.user_id=u.id WHERE wu.wechat_id=? AND wu.subscribe=1 AND wu.is_default=1 GROUP BY u.sex ORDER BY cnt DESC ";
            $portrait = \DB::select($sql, [$wechat->id]);

            $sql = "SELECT u.province,COUNT(1) cnt FROM wechat_user wu LEFT JOIN `user` u ON wu.user_id=u.id WHERE wu.wechat_id=? AND wu.subscribe=1 AND wu.is_default=1 GROUP BY u.province ORDER BY cnt";
            $region = \DB::select($sql, [$wechat->id]);

            $wechat->update(['region' => $region, 'portrait' => $portrait,]);
        });
    }
}
