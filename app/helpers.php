<?php

use App\Models\User;
use App\Models\WechatUser;

if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}
if (!function_exists('api')) {
    function api($class, $method = '', $args = [], $construct = [])
    {
        static $_api = [];
        $identify = empty($args) ? $class . $method : $class . $method . md5(json_encode($args));
        if (!isset($_api[$identify])) {
            if (class_exists($class)) {
                $obj = new $class($construct);
                if (method_exists($obj, $method)) {
                    if (!empty($args)) {
                        $_api[$identify] = call_user_func_array([&$obj, $method], $args);
                    } else {
                        $_api[$identify] = $obj->$method();
                    }
                } else {
                    $_api[$identify] = $obj;
                }
            } else {
                return null;
            }
        }
        return $_api[$identify];
    }
}
if (!function_exists('setting')) {
    function setting($key = '', $value = '')
    {
        $file_path = storage_path('setting.json');
        if (is_file($file_path)) {
            $settings = json_decode(file_get_contents($file_path), true);
        } else {
            $settings = [];
        }

        if ('' === $key) return $settings;

        if (0 === strpos($key, '?')) {
            $default = microtime(true);
            return $default !== array_get($settings, substr($key, 1), $default);
        }

        if (is_null($key)) {
            return file_put_contents($file_path, json_encode([]));
        }

        if ('' === $value) {
            $ts = microtime(true);
            if ($ts !== array_get($settings, $key, $ts)) {
                return array_get($settings, $key);
            }
        } elseif (is_null($value)) {
            array_forget($settings, $key);
            file_put_contents($file_path, json_encode($settings));
        } else {
            array_set($settings, $key, $value);
            file_put_contents($file_path, json_encode($settings));
        }
        return true;
    }
}
//获取公众号
function getApp($appid = null)
{
    if (!$appid) $appid = \App\Models\Wechat::where(['status' => 1, 'is_default' => 1])->value('appid');
    try {
        $openPlatform = app('wechat')->open_platform;
    } catch (\Exception $exception) {
        info($appid."\n".$exception->getMessage());
        alarm($appid."\n".$exception->getMessage());
        return false;
    }
    $authorizer = $openPlatform->authorizer;
    $authorizer->setAppId($appid);
    return $openPlatform->createAuthorizerApplication($authorizer->getAppId(), $authorizer->getRefreshToken());
}

//获取小程序二维码
function getAppCode($scene, $page, $width = 430, $color = '0,0,0')
{
    try {
        list($r, $g, $b) = explode(',', $color);
        $qrcode = base64_encode(app('wechat')->mini_program->qrcode->appCodeUnlimit($scene, $page, $width, false, ['r' => $r, 'g' => $g, 'b' => $b]));
        $ossclient = new \OSS\OssClient(config('filesystems.disks.aliyun.accessKeyId'), config('filesystems.disks.aliyun.accessKeySecret'), config('filesystems.disks.aliyun.endpoint'), true);
        $result = $ossclient->putObject(config('filesystems.disks.aliyun.bucket'), 'appcode/' . md5($qrcode) . '.jpg', base64_decode($qrcode));
        return $result['info']['url'];
    } catch (\Exception $exception) {
        return getAppCode($scene, $page, $width, $color);
    }
}

//获取活跃用户
function getActiveUser($origin = 1, $openid = '')
{
    if ($origin == 1) {
        $origin = \App\Models\Wechat::where(['is_default' => 1, 'status' => 1])->value('appid');
    }
    $redis = app('redis');
    if ($openid) {
        return $redis->exists('mornight:active:' . $origin . ':' . $openid);
    } else {
        $openid_active = $redis->keys('mornight:active:' . $origin . ':*');
        $openid_array = [];
        foreach ($openid_active as $v) {
            $openid_array[] = substr($v, strrpos($v, ':') + 1);
        }
        return $openid_array;
    }
}

//获取微信用户
function getWeChatUser($app, &$openid = [], $next_openid = null)
{
    $user_lists = $app->user->lists($next_openid);
    if (isset($user_lists['data']['openid']) && $user_lists['data']['openid']) {
        foreach ($user_lists['data']['openid'] as $v) {
            $openid[] = $v;
        }
    }
    if (isset($user_lists['next_openid']) && $user_lists['next_openid']) {
        getWeChatUser($app, $openid, $user_lists['next_openid']);
    }
    return $openid;
}

//获取回复内容
function getReply($app, $reply, $content, $origin = 1)
{
    switch ($reply) {
        case 'text':
            return $content;
        case 'image':
            if (strpos($content, "http://") !== false) {
                $image_file = storage_path('app/upload/' . strtolower(substr($content, strrpos($content, '/') + 1)));
                if (!file_exists($image_file)) {
                    if (file_put_contents($image_file, file_get_contents($content)) <= 0) {
                        return false;
                    }
                }
                $content = $image_file;
            }
            if ($origin == 1) {
                $material = $app->material_temporary;
            } else {
                $material = app('wechat')->mini_program->material_temporary;
            }
            $result = $material->uploadImage($content);
            return new \EasyWeChat\Message\Image(['media_id' => $result['media_id']]);
        case 'news':
            $content = json_decode($content);
            $newsArray = [];
            foreach ($content->title as $k => $v) {
                $news = new \EasyWeChat\Message\News([
                    'title' => $v,
                    'description' => $content->description[$k],
                    'url' => $content->url[$k],
                    'image' => $content->image[$k],
                ]);
                $newsArray[$k] = $news;
            }
            return $newsArray;
        default:
            return $content;
    }
}

function sendStaffMsg($reply, $content, $openid, $origin = 1, $appid = null)
{
    if (is_array($origin)) {
        $appid = $origin['appid'];
        $origin = 1;
    }
    $app = getApp($appid);
    if (!$content) return false;
    $data = $content;
    if ($reply == 'image') {
        if (strpos($content, "http://") !== false) {
            $image_file = storage_path('app/upload/' . strtolower(substr($content, strrpos($content, '/') + 1)));
            if (!file_exists($image_file) && file_put_contents($image_file, file_get_contents($content)) <= 0) return false;
            $content = $image_file;
        }
        $redis = app('redis');
        $key = 'mornight:staff:msg:' . md5_file($content);
        $medianId = $redis->get($key);
        if (empty($medianId)) {
            $material = $origin == 1 ? $app->material_temporary : app('wechat')->mini_program->material_temporary;
            try {
                $result = $material->uploadImage($content);
            } catch (Exception $exception) {
                info($_SERVER['SERVER_ADDR'].",文件:" . $content . "发送失败\n".$exception->getMessage());
                return false;
            }
            $medianId = $result['media_id'];
            $redis->setex($key, 600, $medianId);
        }
        $content = new \EasyWeChat\Message\Image(['media_id' => $medianId]);
    }
    if ($reply == 'news') {
        if (is_array($content)) {
            $content = new \EasyWeChat\Message\News($content);
        } else {
            $content = json_decode($content);
            $newsArray = [];
            foreach ($content->title as $k => $v) {
                $news = new \EasyWeChat\Message\News([
                    'title' => $v,
                    'description' => $content->description[$k],
                    'url' => $content->url[$k],
                    'image' => $content->image[$k],
                ]);
                $newsArray[$k] = $news;
            }
            $content = $newsArray;
        }
    }
    try {
        $wechatUser = WechatUser::where(['openid' => $openid])->first();
        if (!empty($wechatUser) && $wechatUser->subscribe != 1 || empty($openid)) return false;
        if ($origin != 1) $app = app('wechat')->mini_program;
        $ret = $app->staff->message($content)->to($openid)->send();
        return !empty($ret) && $ret->errmsg == 'ok';
    } catch (\Exception $exception) {
        if (strpos($exception->getMessage(), "timed out") !== false) return sendStaffMsg($reply, $data, $openid, $origin);
        if (strpos($exception->getMessage(), "response out of time") !== false) !empty($wechatUser) && \DB::table('wechat_user')->where('openid', $openid)->update(['subscribe' => 0]);
        else info('sendStaffMsg', ['reply' => $reply, 'content' => $content, 'openid' => $openid, 'message' => $exception->getMessage() . ':' . $exception->getCode()]);
        return false;
    }
}

function sendTplMsg($tpl_id, $url, $data, $openid, $origin = 2, $appid = null, $miniprogram = [])
{
    try {
        if ($origin == 1) {
            $app = getApp($appid);
            $user = $app->user->get($openid);
            if ($user->subscribe) {
                $post = [
                    'touser' => $openid,
                    'template_id' => $tpl_id,
                    'url' => $url,
                    'data' => $data
                ];
                if ($miniprogram) {
                    $post['miniprogram'] = $miniprogram;
                }
                $app->notice->send($post);
                return true;
            }
        } else {
            $redis = app('redis');
            $keys = $redis->keys('mornight:active:miniprogram:tplmsg:' . $openid . ':*');
            foreach ($keys as $key) {
                $form_id = substr($key, strrpos($key, ':') + 1);
                if ($form_id) {
                    app('wechat')->mini_program->notice->send([
                        'touser' => $openid,
                        'template_id' => $tpl_id,
                        'page' => $url,
                        'form_id' => $form_id,
                        'data' => $data
                    ]);
                    if ($redis->get($key) <= 1) {
                        $redis->del($key);
                    } else {
                        $redis->decr($key);
                    }
                    return true;
                }
            }
        }
        return false;
    } catch (\Exception $exception) {
        if (strpos($exception->getMessage(), "timed out") !== false) {
            return sendTplMsg($tpl_id, $url, $data, $openid);
        } else {
            info('sendTplMsg', ['tpl_id' => $tpl_id, 'url' => $url, 'data' => $data, 'appid' => $appid, 'openid' => $openid, 'message' => $exception->getMessage() . ':' . $exception->getCode()]);
            return false;
        }
    }
}

//发送消息
function sendMsg($user_id, $title, $type, $content)
{
    $log['user_id'] = $user_id;
    $log['title'] = $title;
    $log['type'] = $type;
    $log['content'] = $content;
    $result = \App\Models\UserMsg::create($log);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

//改变原力
//read:阅读;share:分享;article_comment:日报留言;article_like:日报点赞;order:商城消费;top:排行榜;sign:打卡;invite:打卡;moment:发送弹幕;today:每日任务;
//sign21:连续打卡21天;invite5:累计邀请5个好友;moment10:连续10天发弹幕;admin:管理员更改魔币;
function changeCoin($user_id, $number, $action, $action_id, $remark)
{
    $user_id = (int)$user_id;
    $number = (int)$number;
    $log = [];
    $log['user_id'] = $user_id;
    $log['number'] = $number;
    $log['action'] = $action;
    $log['action_id'] = $action_id;
    $log['remark'] = $remark;
    $return = false;
    $result = \App\Models\UserCoin::create($log);
    if ($result) {
        if ($number > 0) {
            $return = \App\Models\User::where('id', $user_id)->increment('coin', $number);
        } else {
            $return = \App\Models\User::where('id', $user_id)->decrement('coin', abs($number));
        }
    }
    //新增一张有序集合表，记录当天每个用户的原力值综合
    $redis = app('redis');
    $redis->zincrby('mornight:account:todayCoin:' . date('Y-m-d'), $number, $user_id);
    if ($return) {
        return true;
    } else {
        return false;
    }
}

//发送订单日志
function sendOrderLog($user_id, $user_order_id, $type, $action, $remark)
{
    $log['user_id'] = $user_id;
    $log['user_order_id'] = $user_order_id;
    $log['type'] = $type;
    $log['action'] = $action;
    $log['remark'] = $remark;
    $result = \App\Models\UserOrderLog::create($log);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function cancelOrder($id)
{
    $order = \App\Models\UserOrder::select('id', 'user_id', 'trade_no', 'product_price', 'express_price', 'created_at')->where('status', '<>', 'canceled')->where(function ($query) use ($id) {
        $query->orWhere('id', $id)->orWhere('trade_no', $id);
    })->firstOrFail();
    //取消订单
    $order_result = $order->update(['status' => 'canceled']);
    //加库存
    $product_spec_result = false;
    foreach ($order->products as $product) {
        $product_spec_result = \App\Models\ProductSpec::where('id', $product->product_spec_id)->increment('stock', $product->number);
    }
    if ($order_result && $product_spec_result) {
        sendTplMsg('DAX3oUrZ1pcmbpN7ayGKOD92dtITSO2OxF8xaZ9tVaw', 'pages/orders/orders', ['keyword1' => $order->trade_no, 'keyword2' => $order->product_price + $order->express_price, 'keyword3' => $order->created_at], $order->user->openid);
        return true;
    } else {
        return false;
    }
}

function refundOrder($trade_no, $fee = 0)
{
    $order = \App\Models\UserOrder::select('id', 'user_id', 'trade_no', 'product_price', 'express_price')->where(['pay_status' => 'paid', 'trade_no' => $trade_no])->firstOrFail();
    $payment = app('wechat')->payment;
    $refund_no = date('YmdHis') . mt_rand(1000, 9999);
    $price = $order->product_price + $order->express_price;
    if ($fee == 0) {
        $fee = $price;
    }
    $result = $payment->refund($order->trade_no, $refund_no, $price * 100, $fee * 100);
    if ($result['return_code'] == 'SUCCESS') {
        if ($result['result_code'] == 'SUCCESS') {
            if ($price == $fee) {
                $order->update(['pay_status' => 'refunded']);
            }
            sendTplMsg('mmVgOGaagEsIrpkk6EqM6Q9fjeU49najJMjSNcJksoA', 'pages/orders/orders', ['keyword1' => $fee, 'keyword2' => $price, 'keyword3' => $trade_no], $order->user->openid);
            sendOrderLog(empty(\Auth::user()) ? 0 : \Auth::user()->id, $order->id, empty(\Auth::user()) ? 'system' : 'admin', 'refund', '订单退款;原金额:' . $price . ',退款编号:' . $refund_no . ',退款金额:' . $fee);
            return true;
        } else {
            info('refundOrder', ['trade_no' => $order->trade_no, 'refund_no' => $refund_no, 'err_code' => $result['err_code'], 'err_code_des' => $result['err_code_des']]);
            return false;
        }
    } else {
        info('refundOrder', ['trade_no' => $order->trade_no, 'refund_no' => $refund_no, 'return_code' => $result['return_code'], 'return_msg' => $result['return_msg']]);
        return false;
    }
}

function syncRegisterUser($appid, $next_openid = '')
{
    $app = getApp($appid);
    try {
        $lists = !empty($next_openid) ? $app->user->lists($next_openid) : $app->user->lists();
    } catch (Exception $exception) {
        alarm($appid . '|' . $exception->getMessage());
        return;
    }
    $redis = app('redis');
    $key = 'syncRegisterUser:' . $appid . '-' . $lists['next_openid'];
    if ($lists['count'] > 0 && !$redis->exists($key) && !empty($lists['data']['openid'])) {
        foreach (array_chunk($lists['data']['openid'], 100) as $openid) {
            dispatch((new \App\Jobs\SyncUserDetail($openid, $appid))->onQueue('batchInsertUser'));
            usleep(rand(5, 20));
        }
        info("{$appid} 新增用户数：" . count($lists['data']['openid']));
        $redis->setex($key, 3600, 1);
    }
    if (!empty($lists['next_openid'])) {
        usleep(rand(5, 20));
        dispatch((new \App\Jobs\SyncUser($appid, $lists['next_openid']))->onQueue('syncRegisterUser'));
    } else {
        info("公众号：" . $appid . " 已经初始化 " . $lists['total'] . " 个粉丝数据");
    }
}

function batchInsertUser($openid, $appid)
{
    $userFieldName = ['openid', 'unionid', 'nickname', 'avatarurl', 'headimgurl', 'gender', 'sex', 'city', 'province', 'country'];
    $wechatUserFieldName = ['subscribe', 'subscribe_time', 'openid'];
    $existsUserOpenids = \App\Models\User::whereIn('openid', $openid)->select('openid')->pluck('openid')->toArray();
    $wxUsers = getApp($appid)->user->batchGet($openid)['user_info_list'];
    $wechat = \App\Models\Wechat::where(['appid' => $appid])->first();
    $users = array_filter($wxUsers, function ($user) use ($existsUserOpenids) {
        return !in_array($user['openid'], $existsUserOpenids);
    });

    $newUser = [];
    foreach ($users as $user) {
        $user = (array)$user;
        $newRow = [];
        foreach ($userFieldName as $field) {
            $newRow[$field] = '';
            if (!empty($user[$field])) {
                $newRow[$field] = $user[$field];
            }
        }
        if (!empty($newRow)) $newUser[] = getData($newRow);
    }
    \DB::table('user')->insert($newUser);
    debug($wechat->appid . "user表 新增" . count($newUser) . "个记录");
    usleep(rand(5, 20));

    $existsUserOpenids = \App\Models\WechatUser::whereIn('openid', $openid)->select('openid')->pluck('openid')->toArray();
    $webchatUsers = array_filter($wxUsers, function ($user) use ($existsUserOpenids) {
        return !in_array($user['openid'], $existsUserOpenids);
    });

    $userInfos = \App\Models\User::whereIn('openid', $openid)->select('openid', 'id')->get()->toArray();
    $webchatUsers = array_map(function ($wechatUser) use ($userInfos, $wechatUserFieldName, $wechat) {
        foreach ($userInfos as $userInfo) {
            if ($wechatUser['openid'] == $userInfo['openid']) {
                $wechatUser = (array)$wechatUser;
                $wechatUser = array_merge(array_intersect_key($wechatUser, array_combine($wechatUserFieldName, array_pad([], 3, 1))), $userInfo);
            }
        }
        $wechatUser['user_id'] = $wechatUser['id'];
        unset($wechatUser['id']);
        return array_merge($wechatUser, ['wechat_id' => $wechat->id, 'is_default' => 1]);
    }, $webchatUsers);
    \DB::table('wechat_user')->insert($webchatUsers);
    debug($wechat->appid . "wechat_user表 新增" . count($webchatUsers) . "个记录");
}

function getData($user_info)
{
    $arr = [];
    foreach ($user_info as $key => $val) {
        $arr[strtolower($key)] = $val;
    }
    $fields = ['openid', 'unionid', 'nickname', 'avatarurl', 'headimgurl', 'gender', 'sex', 'city', 'province', 'country'];
    $tmpArr = array_combine($fields, $fields);
    $data = array_intersect_key($arr, $tmpArr);

    if (!isset($data['headimgurl']) && isset($data['avatarurl']) && $data['avatarurl'] != '') {
        $data['headimgurl'] = $data['avatarurl'];
    }
    unset($data['avatarurl']);

    if (!isset($data['sex']) && isset($data['gender']) && $data['gender'] != '') {
        $data['sex'] = $data['gender'];
    }
    unset($data['gender']);
    return $data;
}

function generalInviteCode()
{
    $num = app('redis')->incr('base62s');
    if ($num < 238328) {
        $num = 238328;
        app('redis')->set('base62s', $num);
    } elseif ($num >= 14503413 && $num <= 14507255) {
        $num = 14507255 + 1;
        app('redis')->set('base62s', $num);
    }
    $to = 62;
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ret = '';
    do {
        $ret = $dict[bcmod($num, $to)] . $ret;
        $num = bcdiv($num, $to);
    } while ($num > 0);
    return $ret;
}

function getUser($user_info, $wechat = null)
{
    $userRow = getData($user_info);
    $is_new = true;
    $miniUser = $orign = \App\Models\User::where(['openid' => $userRow['openid']])->first();
    if (is_null($miniUser)) {
        $insertUser = [
            'config' => [
                'sign' => 1,
                'walk' => 1,
                'invite' => 1,
                'remind' => $wechat ? $wechat->id : 0,
                'sign_time' => '07:00',
                'walk_time' => '21:00'
            ]
        ];
        $insertUser = array_merge($userRow, $insertUser);
        $key_prefix_redis = 'getUser:' . __METHOD__ . ':';
        $userModel = synchronousHandle($key_prefix_redis . $userRow['openid'], function () use ($insertUser) {
            return \App\Models\User::create($insertUser);
        });
        if ($userModel == 'insync') {
            throw new Exception('程序正在执行...');
        }
        //新用户redis 48小时内打卡特权
        $redis = app('redis');
        $redis->set('mornight:account:fisher:' . $userModel->id, 'fisher');
        $redis->expire('mornight:account:fisher:' . $userModel->id, 172800);

        $miniUser = $userModel;

    } else {
        $miniUser->update($userRow);
    }

    if ($wechat) {
        $sync = [];
        $sync['openid'] = $miniUser->openid;
        if (isset($user_info['subscribe'])) {
            $sync['subscribe'] = $user_info['subscribe'];
        }
        if (isset($user_info['subscribe_time']) && !is_null($orign)) {
            $sync['subscribe_time'] = $user_info['subscribe_time'];
            $is_new = false;
        }
        $lock = 'locl:getUser:' . $userRow['openid'];
        $redis = app('redis');
        if (!$redis->exists($lock)) {
            $redis->setex($lock, 300, 1);
            if (WechatUser::where('openid', $userRow['openid'])->doesntExist()) {
                \DB::table('wechat_user')->insert(array_merge($sync, ['user_id' => $miniUser->id, 'wechat_id' => $wechat->id]));
            } else {
                \DB::table('wechat_user')->where('openid', $userRow['openid'])->update(array_merge($sync, ['user_id' => $miniUser->id, 'wechat_id' => $wechat->id]));
            }
            $redis->del($lock);
        }
        $miniUser->update(['is_mini_user' => 0]);
    } else {
        if (WechatUser::where('openid', $userRow['openid'])->doesntExist()) {
            $lastAppid = null;
            \App\Models\User::where(['unionid' => $miniUser->unionid])->where('id', '<>', $miniUser->id)->get()->each(function ($chenxiUser, $key) use ($miniUser, &$lastAppid) {
                $chenxiUser->update([
                    'is_mini_user' => 0,
                    'mini_user_id' => $miniUser->id
                ]);
                if (!empty($chenxiUser->last_appid)) {
                    $lastAppid = $chenxiUser->last_appid;
                }
                \App\Models\User::where(['mini_user_id' => $chenxiUser->id])->get()->each(function ($gzhUser, $k) use ($miniUser) {
                    $gzhUser->update([
                        'is_mini_user' => 0,
                        'mini_user_id' => $miniUser->id
                    ]);
                });
            });
            $updateData = ['is_mini_user' => 1, 'mini_user_id' => 0];
            if (!empty($lastAppid)) {
                $updateData += ['last_appid' => $lastAppid];
            }
            $miniUser->update($updateData);
        }
    }
    $miniUser->is_new = $is_new;
    return $miniUser;
}

function send_mini_card($openid, $gzh_appid)
{
    $mini_appid = config('wechat.mini_program.app_id');
    $pagepath = 'pages/index/index?appid=' . $gzh_appid . '&openid=' . $openid;
    $params = "{
        \"touser\":\"{$openid}\",
        \"msgtype\":\"text\",
        \"text\":
        {
             \"content\":\"<a href='http://www.cxyun.com' data-miniprogram-appid='{$mini_appid}' data-miniprogram-path='{$pagepath}'>点此打卡，领取今日奖励！>></a>\"
        }
    }";
    $token = getApp($gzh_appid)->access_token->getToken();
    $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $token;
    $http = new \EasyWeChat\Core\Http();
    return $http->post($api, $params)->getBody();
}

function synchronousHandle($lock, $func)
{
    $redis = app('redis');
    if ($redis->exists($lock)) {
        return 'insync';
    }
    $redis->setex($lock, 3600, 1);
    $ret = null;
    try {
        $ret = $func();
        $redis->del($lock);
        return $ret;
    } catch (Exception $e) {
        $redis->del($lock);
        throw new Symfony\Component\HttpKernel\Exception\HttpException(400, $e->getMessage());
    }
}

function getDakaYuanli_v2($day)
{
    //签到积分
    $now_hour = date('H');
    if ($now_hour >= 5 && $now_hour < 8) {
        $plus = 100;
    } elseif ($now_hour >= 8 && $now_hour < 10) {
        $plus = 50;
    } else {
        $plus = 10;
    }
    //活跃积分
    $activity_coin = $day > 1 ? $day * 3 : 0;
    $activity_coin = $activity_coin > 60 ? 60 : $activity_coin;
    $plus += $activity_coin;
    return $plus;
}

function incrementBlueDiamond($wechatId, $user_id, $blueDiamond, $remark, $action = 'sign_increment')
{
    \App\Models\User::where('id', $user_id)->increment('blue_diamond', $blueDiamond);
    $log = new \App\Models\UserBlueDiamond();
    $log->user_id = $user_id;
    $log->number = $blueDiamond;
    $log->remark = $remark;
    $log->action = $action;
    $log->wechat_id = $wechatId;
    $log->save();
}

//新手任务特殊奖励
function fisherMissionAward($wechatId, $user_id, $type)
{
    $number = 0;
    $missions = fisherMission($user_id);
    if ($type == 'sign' && $missions->sign == 0) {
        $number = 8;
        $remark = '完成新手任务:初次打卡，额外奖励' . $number . '个蓝钻。';
        $action = 'fisher_sign';
        $missions->sign = 1;
    }
    if ($type == 'message' && $missions->message == 0) {
        $number = 1;
        $remark = '完成新手任务:初次留言，额外奖励' . $number . '个蓝钻。';
        $action = 'fisher_message';
        $missions->message = 1;
    }
    if ($type == 'read' && $missions->read == 0) {
        $number = 5;
        $remark = '完成新手任务:初次阅读，额外奖励' . $number . '个蓝钻。';
        $action = 'fisher_read';
        $missions->read = 1;
    }
    if (isset($action)) {
        $missions->save();
        incrementBlueDiamond($wechatId, $user_id, $number, $remark, $action);
    }
    return $number;
}

function getMission($user)
{
    $data['sign'] = $user->signs()->where('date', date('Y-m-d'))->count();
    $data['walk'] = $user->coins()->where('action', 'walk')->where('created_at', '>', date('Y-m-d'))->count();
    $walk = $user->walks()->where('date', date('Y-m-d'))->first();
    if ($walk) {
        $data['give'] = $walk->to_gives()->where('created_at', '>', date('Y-m-d'))->count();
    } else {
        $data['give'] = 0;
    }
    $data['moment'] = app('redis')->hexists("mornight:moment:" . \Auth::user()->last_appid . ":user", $user->id);
    $data['like'] = $user->likes()->where('created_at', '>', date('Y-m-d'))->count();
    return $data;
}

function remainSecondsForToday()
{
    return strtotime(date('Y-m-d', strtotime('+1 day'))) - time();
}

function useRemainSettingInfoForThirdGzh($appid, $filter = '公众号授权第三方平台-早起提醒')
{
    $model_chenxi_wechat = \App\Models\Wechat::where(['appid' => 'wxa7852bf49dcb27d7'])->first();
    $model_other_wechat = \App\Models\Wechat::where(['appid' => $appid])->first();
    $reply = \App\Models\WeChatReply::where(['wechat_id' => $model_chenxi_wechat->id, 'keyword' => $filter])->first()->toArray();
    $reply = array_except($reply, ['id', 'wechat_id', 'created_at', 'updated_at']);
    \App\Models\WeChatReply::updateOrCreate(['wechat_id' => $model_other_wechat->id, 'keyword' => $filter], $reply);
    $model_other_wechat->update(['media_id' => $model_chenxi_wechat->media_id]);
}

function curlFileGetContents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function debug(...$obj)
{
    $log = storage_path('debug.info');
    file_put_contents($log, date('Y-m-d H:i:s') . '--' . print_r($obj, 1), FILE_APPEND);
}

function smsSend($phone)
{
    $appKey = env('ALIYUN_ACCESS_KEY', 'LTAIr8ada3I1Qg5U');
    $appSecret = env('ALIYUN_ACCESS_SECRET', '9zVdQe7N3i8X2yVsldS06udi6Kyof6');
    $signName = '注册验证';
    $template_code = 'SMS_18315677';
    $code = mt_rand(100000, 999999);
    $json_string_param = GuzzleHttp\json_encode(["code" => strval($code), "product" => "晨夕时刻"], JSON_UNESCAPED_UNICODE);

    Aliyun\Core\Config::load();
    $profile = Aliyun\Core\Profile\DefaultProfile::getProfile("cn-hangzhou", $appKey, $appSecret);
    Aliyun\Core\Profile\DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Dysmsapi", "dysmsapi.aliyuncs.com");
    $acsClient = new Aliyun\Core\DefaultAcsClient($profile);
    $request = new Aliyun\Api\Sms\Request\V20170525\SendSmsRequest();
    $request->setPhoneNumbers($phone);
    $request->setSignName($signName);
    $request->setTemplateCode($template_code);

    if (!empty($json_string_param)) {
        $request->setTemplateParam($json_string_param);
    }
    $acsResponse = $acsClient->getAcsResponse($request);
    if ($acsResponse && strtolower($acsResponse->Code) == 'ok') {
        return $code;
    }
    info(print_r($acsResponse, 1));
}

function getTagIdFordoesntExistCustomMenu($tag)
{
    $redirectLinkeText = '跳转或者链接';
    foreach ($tag->lists()['tags'] as $tagItem) {
        if ($tagItem['name'] == $redirectLinkeText) {
            return $tagItem['id'];
        }
    }
    return $tag->create($redirectLinkeText)->toArray()['tag']['id'];
}

function alarm($info)
{
    sendStaffMsg('text', $info, 'oHJsq093DYIu8opunOO98bfC01zg', 1, 'wxa7852bf49dcb27d7');
}

//计算21天原力值
function calculateCoin($uid)
{
    return \App\Models\UserCoin::where('user_id', $uid)
        ->where('created_at', '>', date('Y-m-d', strtotime('-21 day')))
        ->where('created_at', '<', date('Y-m-d'))
        ->sum('number');
}

//根据coin获取用户当前的等级
function calculateLevel($coin)
{
    return $coin <= 500 ? 'lv1' : ($coin >= 501 && $coin <= 3000 ? 'lv2' : ($coin >= 3001 && $coin <= 4500 ? 'lv3' : 'lv4'));
}

//根据等级获取当前蓝钻增长率
function calculateDiamond($level)
{
    return $level == 'lv1' ? 1 : ($level == 'lv2' ? 2 : ($level == 'lv3' ? 4 : 6));
}

//获取今天剩余时间戳
function untilTomorrow()
{
    return abs(strtotime(date('Y-m-d 23:59:59')) - time());
}

//获取新手任务模型
function fisherMission($uid)
{
    return \App\Models\FisherMission::firstOrCreate(['user_id' => $uid], ['sign' => 0, 'message' => 0, 'read' => 0]);
}

function echoJson($status, $message, $arr = [])
{
    echo json_encode(["status" => $status, "message" => $message, "result" => $arr]);
    exit();
}

function getEnableGzh()
{
    return \App\Models\Wechat::where('status', 1)->whereIn('type', [1, 2])->where('owner', '<>', '个人')->get();
}

function getGzhStatic($appid)
{
    $redis = app('redis');
    $redisKey = 'tmp:saas:tongji:' . $appid;
    if (empty($data = $redis->get($redisKey))) {
        $wechat = \App\Models\Wechat::where(['appid' => $appid])->first();
        $todayWechatStats = \App\Models\WechatStats::where('appid', $appid)->where('date', date('Y-m-d'))->first();
        $preWechatStats = \App\Models\WechatStats::where('appid', $appid)->where('date', date('Y-m-d', strtotime('-1 day')))->first();
        $quguanCount = \App\Models\WechatUser::where(['wechat_id' => $wechat->id])->where('discribe_time', '>', date('Y-m-d'))->count();
        $guanzhuCount = \App\Models\UserInvite::where('created_at', '>', date('Y-m-d'))->where('appid', $appid)->count();
        $data = compact('todayWechatStats', 'preWechatStats', 'quguanCount', 'guanzhuCount');
        $redis->setex($redisKey, 900, \GuzzleHttp\json_encode($data));
    } else {
        $data = \GuzzleHttp\json_decode($redis->get($redisKey), true);
    }
    return $data;
}

function importModubusUserInfo($middleUser, $gzhUser)
{
    $connect = \DB::connection('mysql_modubus');
    $modubusInfo = $connect->table('user')->where('unionid', $gzhUser->unionid)->first();
    if (empty($modubusInfo)) return;

    $modubusUserSignList = $connect->table('user_sign')->where(['user_id' => $modubusInfo->id])
        ->where('date', '>=', date('Y-m-d', strtotime('-' . ($modubusInfo->day + 5) . ' day')))
        ->get()->toArray();
    foreach (array_chunk($modubusUserSignList, 500) as $modubusUserSignObjs) {
        $signs = [];
        foreach ($modubusUserSignObjs as $modubusUserSignObj) {
            $modubusUserSign = (array)$modubusUserSignObj;
            unset($modubusUserSign['id']);
            if (\App\Models\UserSign::where(['user_id' => $middleUser->id, 'date' => $modubusUserSignObj->date])->exists()) continue;
            $modubusUserSign['user_id'] = $middleUser->id;
            $modubusUserSign['appid'] = 'wxe2eabd694a9f7a94';
            $modubusUserSign['wechat_id'] = 18;
            $modubusUserSign['hour'] = date('H', strtotime($modubusUserSignObj->created_at));
            $modubusUserSign['created_at'] = $modubusUserSignObj->created_at;
            $modubusUserSign['updated_at'] = $modubusUserSignObj->updated_at;
            $signs[$middleUser->id . $modubusUserSignObj->date] = $modubusUserSign;
        }
        usleep(20 * 1000);
        \DB::table('user_sign')->insert(array_values($signs));
    }


    $modubusUserCoinObjs = $connect->table('user_coin')
        ->where(['user_id' => $modubusInfo->id])
        ->where('created_at', '>=', date('Y-m-d', strtotime('-22 day')))
        ->get()
        ->toArray();
    $coins = [];
    foreach ($modubusUserCoinObjs as $modubusUserCoinObj) {
        $modubusUserCoin = (array)$modubusUserCoinObj;
        if (\App\Models\UserCoin::where(['user_id' => $middleUser->id, 'relationship_id' => $modubusUserCoin['id']])->exists()) continue;
        $modubusUserCoin['remark'] = str_replace('魔币', '原力', $modubusUserCoin['remark']);
        $modubusUserCoin['user_id'] = $middleUser->id;
        $modubusUserCoin['relationship_id'] = $modubusUserCoin['id'];
        unset($modubusUserCoin['id']);
        $coins[] = $modubusUserCoin;
    }
    \DB::table('user_coin')->insert($coins);

    $totalCoin = \App\Models\UserCoin::where('user_id', $middleUser->id)
        ->where('created_at', '>', date('Y-m-d', strtotime('-21 day')))
        ->where('created_at', '<', date('Y-m-d'))
        ->sum('number');
    $middleUser->update(['coin' => $totalCoin]);
    updateUserSignDay($middleUser);
}

function updateUserSignDay($user)
{
    $dateList = \App\Models\UserSign::where('user_id', $user->id)->orderBy('date', 'desc')->pluck('date')->toArray();
    $dateList = array_values(array_unique($dateList));
    for ($i = 0; $i < count($dateList) - 1; $i++) {
        if (date('Y-m-d', strtotime($dateList[$i]) - 86400) != $dateList[$i + 1]) {
            break;
        }
    }
    if (isset($dateList[0])) {
        \App\Models\User::find($user->id)->update(['day' => $i + 1]);
        \App\Models\UserSign::where(['user_id' => $user->id, 'date' => $dateList[0]])->update(['day' => $i + 1]);
    }
}

function fixUserSign()
{
    $userIds = User::whereIn('openid', getActiveUser('wxe2eabd694a9f7a94'))->where('mini_user_id', '>', 0)->pluck('mini_user_id');
    $miniUsers = User::whereIn('id', $userIds)->with(['signs' => function ($query) {
        $query->where(['date' => date('Y-m-d')]);
    }])->get();

    foreach ($miniUsers as $miniUser) {
        if (empty($userSign = $miniUser->signs->toArray())) continue;
        $userSign = $userSign[0];
        if ($userSign['day'] == $miniUser->day) continue;
        $dateList = \App\Models\UserSign::where('user_id', $miniUser->id)->orderBy('date', 'desc')->pluck('date')->toArray();
        $dateList = array_values(array_unique($dateList));
        $len = count($dateList);
        $i = 0;
        for (; $i < $len - 1; $i++) {
            if (date('Y-m-d', strtotime($dateList[$i]) - 86400) != $dateList[$i + 1]) {
                break;
            }
        }
        echo $miniUser->id, "\t";
        if ($miniUser->day != $i + 1) {
            echo $miniUser->day, "\t", ($i + 1), "\t", \App\Models\UserSign::where('user_id', $miniUser->id)->orderBy('date', 'desc')->value('day');
            \App\Models\User::find($miniUser->id)->update(['day' => $i + 1]);
            UserSign::find($userSign['id'])->update(['day' => $i + 1]);
        }
        echo "\n";
    }
}