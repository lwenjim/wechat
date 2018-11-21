<?php

namespace App\Http\Controllers;

use App\Models\TokenUsers;
use App\Models\User;
use App\Models\UserOpenidCorrelationMini;
use App\Models\Wechat;
use App\Models\AppCode;
use App\Jobs\Kefu;
use App\Jobs\Msg;
use App\Models\WechatUser;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use App\Models\WechatUserSubscribeLogs;
use App\Models\UserSign;
use App\Models\MaterialText;
use App\Models\UserOpenidCorrelation;
use Mockery\Exception;

class WeChatController extends Controller
{
    public function miniprogram()
    {
        $server = app('wechat')->mini_program->server;
        $server->setMessageHandler(function ($message) {
            $redis = app('redis');
            if (in_array($message->MsgType, ['text', 'image', 'voice', 'video', 'shortvideo', 'location', 'link', 'miniprogrampage']) || ($message->MsgType == 'event' && in_array($message->Event, ['subscribe', 'user_enter_tempsession', 'SCAN', 'CLICK', 'VIEW']))) {
                $redis->setex('mornight:active:miniprogram:' . $message->FromUserName, 172800, date('Y-m-d H:i:s'));
                $active_count = count($redis->keys('mornight:active:miniprogram:*'));
                if ($redis->hget('mornight:active-miniprogram', date('Y-m-d')) < $active_count) {
                    $redis->hset('mornight:active-miniprogram', date('Y-m-d'), $active_count);
                }
            }
            $app = app('wechat');
            $wechat = Wechat::select('id', 'appid')->where(['is_default' => 1, 'status' => 1])->first();
            switch ($message->MsgType) {
                case 'event':
                    if ($message->Event == 'user_enter_tempsession') {
                        if ($message->SessionFrom) {
                            if (strpos($message->SessionFrom, ':') !== false) {
                                list($session, $content) = explode(':', $message->SessionFrom);
                            } else {
                                $content = $message->SessionFrom;
                                $session = 1;
                            }
                            return $this->reply($wechat, $app, 'keyword', $content, $message->FromUserName, 'text', 2, $session);
                        }
                    } else {
                        return '';
                    }
                    break;
                case 'text':
                    return $this->reply($wechat, $app, 'keyword', $message->Content, $message->FromUserName, $message->MsgType, 2);
                case 'image':
                    return $this->reply($wechat, $app, 'image', $message->PicUrl, $message->FromUserName, $message->MsgType, 2);
                default:
                    return $this->reply($wechat, $app, 'default');
            }
        });
        return $server->serve();
    }

    public function platform()
    {
        $openPlatform = app('wechat')->open_platform;
        $openPlatform->server->setMessageHandler(function ($event) use ($openPlatform) {
            switch ($event->InfoType) {
                case 'authorized':
                    $this->refreshAppid($openPlatform, $event->AuthorizationCode);
                    break;
                case 'unauthorized':
                    debug($event->AuthorizerAppid . "取消授权");
                    Wechat::where('appid', $event->AuthorizerAppid)->update(['status' => 0]);
                    break;
                case 'updateauthorized':
                    break;
            }
        });
        return $openPlatform->server->serve();
    }

    public function refreshAppid($openPlatform, $auth_code)
    {
        $authorizer = $openPlatform->authorizer;
        $authorizationInfo = $authorizer->getApi()->getAuthorizationInfo($auth_code)->toArray();
        $authorizationInfo = $authorizationInfo['authorization_info'];
        $authorizer->setAppId($authorizationInfo['authorizer_appid']);
        $authorizer->setAccessToken($authorizationInfo['authorizer_access_token']);
        $authorizer->setRefreshToken($authorizationInfo['authorizer_refresh_token']);
        app('redis')->lpush('token', print_r($authorizationInfo, 1));
        $authorizerInfo = $authorizer->getApi()->getAuthorizerInfo($authorizationInfo['authorizer_appid'])->toArray();
        $wechat = Wechat::where('appid', $authorizationInfo['authorizer_appid'])->first();
        $info = $authorizerInfo['authorizer_info'];
        $data['appid'] = $authorizationInfo['authorizer_appid'];
        $data['name'] = $info['nick_name'];
        if (isset($info['head_img'])) {
            $data['headimgurl'] = $info['head_img'];
        }
        $data['wechat'] = $info['alias'];
        $data['owner'] = $info['principal_name'];
        $data['qrcode'] = $info['qrcode_url'];
        $data['originid'] = $info['user_name'];
        $data['type'] = $info['service_type_info']['id'];
        $data['status'] = 1;
        if ($wechat) {
            $wechat->update($data);
        } else {
            Wechat::create($data);
        }
        return $authorizationInfo;
    }

    public function platformAuth($id = null)
    {
        $openPlatform = app('wechat')->open_platform;
        if (!$this->request->has('auth_code')) return $openPlatform->pre_auth->redirect($this->request->fullUrl());
        $authorizationInfo = $this->refreshAppid($openPlatform, $this->request->input('auth_code'));
        if (!empty($id)) {
            $tUser = TokenUsers::find($id);
            $tUser->appid .= '|' . $authorizationInfo['authorizer_appid'];
            $tUser->cur_appid = $authorizationInfo['authorizer_appid'];
            $tUser->save();
        }
        dispatch((new \App\Jobs\SyncUser($authorizationInfo['authorizer_appid']))->onQueue('syncRegisterUser'));
        debug('新加入' . $authorizationInfo['authorizer_appid']);
        useRemainSettingInfoForThirdGzh($authorizationInfo['authorizer_appid']);
        useRemainSettingInfoForThirdGzh($authorizationInfo['authorizer_appid'], '公众号授权第三方平台-晚读提醒');
        header('location:https://www.cxyun.com/pingtai/#/guid');
        return;
    }

    public function platformPush($appid)
    {
        $wechat = Wechat::select('id', 'appid', 'is_default', 'media_id', 'type', 'owner')->where(['appid' => $appid, 'status' => 1])->first();
        $app = getApp($appid);
        $server = $app->server;
        $server->setMessageHandler(function ($message) use ($wechat, $app) {
            if (empty($wechat) || $wechat->owner == '个人') return '公众号未授权!';
            $redis = app('redis');
            if (in_array($message->MsgType, ['text', 'image', 'voice', 'video', 'shortvideo', 'location', 'link', 'miniprogrampage']) || ($message->MsgType == 'event' && in_array($message->Event, ['subscribe', 'SCAN', 'CLICK', 'VIEW']))) {
                $this->enableGzh($wechat, $message->FromUserName);
            }
            switch ($message->MsgType) {
                case 'event':
                    if ($message->Event == 'subscribe') {
                        $user = getUser($app->user->get($message->FromUserName), $wechat);
                        if ($message->EventKey) {
                            $id = substr($message->EventKey, 8);
                            WechatUserSubscribeLogs::create([
                                'appid' => $wechat->appid,
                                'scene' => $id,
                                'user_id' => $user->id
                            ]);
                            if (is_numeric($id)) {
                                if ($id > 100000000) {
                                    return $this->qrcode($wechat, $app, $id, $user);
                                }
                                if ($user->is_new && User::find($id)) {
                                    dispatch(new \App\Jobs\Invite($user, $id, $wechat->appid));
                                }
                            }
                            return $this->qrcode($wechat, $app, $id, $user);
                        }
                        return $this->reply($wechat, $app, 'subscribe');
                    } else if ($message->Event == 'unsubscribe') {
                        $user = User::where(['openid' => $message->FromUserName])->first();
                        if (empty($user)) return '';
                        $wechat->users()->updateExistingPivot($user->id, ['subscribe' => 0, 'discribe_time' => time()]);
                        $redis->del('mornight:active:' . $wechat->appid . ':' . $message->FromUserName);
                    } else if ($message->Event == 'SCAN') {
                        $user = getUser($app->user->get($message->FromUserName), $wechat);
                        if (is_numeric($message->EventKey)) {
                            if ($message->EventKey >= 10000000 && $message->EventKey <= 50000000 && $wechat->is_default) {
                                if (isset($user->config['admin']) && $user->config['admin'] == 1) {
                                    $data['nickname'] = $user->nickname;
                                    $data['headimgurl'] = $user->headimgurl;
                                    $data['token'] = \Auth::fromUser($user);
                                    Cache::put('wechat:qrcode:' . $message->EventKey, json_encode($data), 2);
                                    return '欢迎' . $user->nickname . '登陆后台！';
                                }
                                return '您无权登陆！';
                            } elseif ($message->EventKey >= 50000001 && $message->EventKey <= 100000000 && $wechat->is_default) {
                                $tokenUser = TokenUsers::where('openid', $message->FromUserName)->first();
                                if (!empty($tokenUser)) {
                                    $api_token = str_random(60);
                                    $tokenUser->update(['api_token' => $api_token]);
                                    Cache::put('wechat:qrcode:pingtai:' . $message->EventKey, json_encode(['api_token' => $api_token]), 2);
                                    return '欢迎' . $user->nickname . '微信用户以' . $tokenUser->username . '的身份登陆saas后台！';
                                } else {
                                    Cache::put('wechat:qrcode:pingtai:' . $message->EventKey, json_encode(['openid' => $message->FromUserName]), 2);
                                    return '未找到绑定的云平台账号';
                                }
                            } elseif ($message->EventKey > 100000000) {
                                return $this->qrcode($wechat, $app, $message->EventKey, $user);
                            }
                            return $this->reply($wechat, $app, 'subscribe');
                        } else {
                            if (stripos($message->EventKey, 'bindQrcode') !== false) {
                                $tokenUserId = (int)str_replace('bindQrcode', '', $message->EventKey);
                                $tokenUser = TokenUsers::find($tokenUserId);
                                $tokenUser->update(['openid' => $message->FromUserName]);
                                return '云平台账号为:' . $tokenUser->username . '成功绑定到昵称为:' . $user->nickname . '的微信号';
                            }
                            return $this->qrcode($wechat, $app, $message->EventKey, $user);
                        }
                    } else if ($message->Event == 'CLICK') {
                        if ($message->EventKey == 'chenxishike') {
                            $openid = $message->FromUserName;
                            $gzh_appid = $wechat['appid'];
                            send_mini_card($openid, $gzh_appid);
                            $userInfo = getApp($gzh_appid)->user->get($openid);
                            $wechat = Wechat::where(['appid' => $gzh_appid])->first();
                            getUser($userInfo, $wechat);
                            return '';
                        } elseif ($message->EventKey == 'chenxishikeh5') {
                            $url = 'https://www.cxyun.com/h5?appid=' . $wechat->appid . '&openid=' . $message->FromUserName;
                            return "<a href='{$url}'>点此打卡，领取今日奖励！>></a>";
                        } elseif (substr($message->EventKey, 0, 4) == 'text') {
                            $key = substr($message->EventKey, 5);
                            $textRow = MaterialText::where(['key' => $key])->first();
                            if (!$textRow) return '';
                            return $textRow->txt;
                        } elseif (substr($message->EventKey, 0, 5) == 'image') {
                            $mediaId = substr($message->EventKey, 6);
                            $text = new \EasyWeChat\Message\Image(['media_id' => $mediaId]);
                            $app->staff->message($text)->to($message->FromUserName)->send();
                            return '';
                        } elseif (substr($message->EventKey, 0, 4) == 'news') {
                            $mediaId = substr($message->EventKey, 5);
                            $newsRows = $app->material->get($mediaId);
                            $news = [];
                            foreach ($newsRows['news_item'] as $row) {
                                $news[] = new \EasyWeChat\Message\News([
                                    'title' => $row['title'],
                                    'description' => $row['digest'],
                                    'url' => $row['url'],
                                    'image' => $row['thumb_url'],
                                ]);
                            }
                            $app->staff->message($news)->to($message->FromUserName)->send();
                            return '';
                        } elseif (substr($message->EventKey, 0, 5) == 'voice') {
                            $mediaId = substr($message->EventKey, 6);
                            $voice = new \EasyWeChat\Message\Voice(['media_id' => $mediaId]);
                            $app->staff->message($voice)->to($message->FromUserName)->send();
                            return '';
                        } elseif (substr($message->EventKey, 0, 5) == 'video') {
                            $mediaId = substr($message->EventKey, 6);
                            $newsRows = $app->material->get($mediaId);
                            $video = new \EasyWeChat\Message\Video([
                                'title' => $newsRows['title'],
                                'media_id' => $mediaId,
                                'description' => $newsRows['description'],
                            ]);
                            $app->staff->message($video)->to($message->FromUserName)->send();
                            return '';
                        }
                        return $this->reply($wechat, $app, 'menu_click', $message->EventKey, $message->FromUserName);
                    } else if ($message->Event == 'VIEW') {
                        return $this->reply($wechat, $app, 'menu_view', $message->EventKey, $message->FromUserName);
                    } else {
                        return '';
                    }
                    break;
                case 'text':
                    return $this->reply($wechat, $app, 'keyword', $message->Content, $message->FromUserName, $message->MsgType, 1);
                case 'image':
                    return $this->reply($wechat, $app, 'image', $message->PicUrl, $message->FromUserName, $message->MsgType, 1);
                default:
                    return $this->reply($wechat, $app, 'default');
            }
        });
        return $server->serve();
    }

    public function reply($wechat, $app, $receive, $content = '', $openid = '', $type = '', $origin = '', $session = 1)
    {
        $miniUser = null;
        $gzhUser = User::where('openid', $openid)->first();
        if ($gzhUser) {
            $gzhUser->update(['last_active_date' => time()]);
            $miniUser = $gzhUser->switchToMiniUser();
        }
        switch ($content) {
            case 1127:
                return count(getWeChatUser($app));
            case 1234:
                return count(getActiveUser($wechat->appid));
            case '明日卡片':
                if (!$miniUser) return '用户初始化失败，请点击菜单并根据回复的链接进行用户初始化！';
                $days = UserSign::where('user_id', $miniUser->id)->count();
                $achieve_primary = \App\Services\Card::achieve($miniUser, $miniUser->city, $days + 1, ($miniUser->day) + 1, date('Y-m-d H:i:s', strtotime('+1 day')), $wechat->appid, 'primary', 'tomorrow');
                return ($url = getReply($app, 'image', $achieve_primary))?$url:'成就卡不存在！';
            case 1:
                if (!$miniUser) return '用户初始化失败，请点击菜单并根据回复的链接进行用户初始化！';
                $days = UserSign::where('user_id', $miniUser->id)->count();
                $primaryImgUrl = \App\Services\Card::achieve($miniUser, $miniUser->city, $days, $miniUser->day, '今日未打卡', $wechat->appid);
                return $primaryImgUrl ? getReply($app, 'image', $primaryImgUrl) : '成就卡不存在！';
            case "查询汇总数据1024":
                $result = getGzhStatic($wechat->appid);
                $count = intval($result['todayWechatStats']['count']);
                $active =  intval($result['todayWechatStats']['active']);
                $sign = intval($result['todayWechatStats']['sign']);
                return $count . '-' . $active . '-' . $sign;
            case "查询打卡1024":
                $punchClockNum = (new \App\Models\UserSign())->getCardTimeScatterOfSingleWechat(1, 40, date('Y-m-d'));
                $filters = [
                    date('H'),
                    date('H', strtotime('-1 hour')),
                    date('H', strtotime('-2 hour')),
                    date('H', strtotime('-3 hour')),
                ];
                $ret = [];
                foreach ($filters as $filter) {
                    $ret[] = empty($punchClockNum[$filter]) ? 0 : $punchClockNum[$filter]['punch_clock_count'];
                }
                return implode('-', $ret);
            default:
                $reply = $wechat->reply()->select('id', 'reply', 'content')->where(['receive' => $receive, 'status' => 1])->when($content, function ($query) use ($content) {
                    return $query->where('keyword', 'like', '%' . $content . '%');
                })->first();
                if ($reply) return getReply($app, $reply->reply, $reply->content, $origin);
                if ($type) {
                    $user_id = User::where('openid', $openid)->value('id');
                    if ($origin == 1) {
                        $user_id = $wechat->user('openid', $openid)->value('id');
                    }
                    if (!$user_id) {
                        info("valid user：{$wechat->appid}-{$openid}-{$content}");
                        return '';
                    }
                    dispatch(new Msg(['openid' => $openid, 'type' => $type, 'content' => $content, 'appid' => $wechat->appid]));
                    dispatch(new Kefu(['user_id' => $user_id, 'openid' => $openid, 'type' => $type, 'origin' => $origin, 'session' => $session, 'content' => $content]));
                } else {
                    if ($receive == 'subscribe') return '感谢您的关注！';
                }
        }
    }

    public function qrcode($wechat, $app, $id, $user)
    {
        $qrcode = $wechat->qrcodes()->select('id', 'keyword')->where(['scene' => $id, 'status' => 1])->first();
        if ($qrcode) {
            $qrcode_user = $qrcode->users()->where('user_id', $user->id)->first();
            if ($qrcode_user) {
                $qrcode_user->increment('number');
            } else {
                $qrcode->users()->create(['user_id' => $user->id, 'is_new' => (int)$user->is_new]);
            }
            if ($qrcode->keyword) {
                return $this->reply($wechat, $app, 'keyword', $qrcode->keyword, $user->openid);
            }
        }
        return $this->reply($wechat, $app, 'subscribe');
    }

    public function admin()
    {
        if ($this->request->isMethod('post')) {
            $code = $this->request->input('code');
            $data = Cache::get('wechat:qrcode:' . $code);
            if ($data) return json_decode($data, true);
            return $this->noContent();
        }
        $code = mt_rand(10000000, 50000000);
        $qrcode = getApp()->qrcode;
        $result = $qrcode->temporary($code, 120);
        return ['url' => $qrcode->url($result->ticket), 'code' => $code];
    }

    public function login()
    {
        $miniProgram = app('wechat')->mini_program;
        $scene = $this->request->get('scene');
        $page = $this->request->get('page');
        $code = $this->request->get('code');
        $data = $this->request->get('data');
        $iv = $this->request->get('iv');
        if (!$code || !$data || !$iv) return $this->errorUnauthorized('error');
        $session = $miniProgram->sns->getSessionKey($code);
        $userInfo = $miniProgram->encryptor->decryptData($session['session_key'], $iv, $data);
        $user = getUser($userInfo);

        if (!$user) return $this->errorUnauthorized('no user');
        $this->bindGzhToMini($user);
        $is_new = 0;
        if (empty($user->openid_at)) {
            unset($user->is_new);
            $user->update(['openid_at' => date('Y-m-d H:i:s')]);
            $is_new = 1;
        }
        if ($scene) {
            if (is_numeric($scene) && $is_new) {
                dispatch(new \App\Jobs\Invite($user, $scene));
            } else {
                $appcode = AppCode::select('id', 'name')->where(['scene' => $scene, 'status' => 1])->when($page, function ($query) use ($page) {
                    return $query->where('page', $page);
                })->first();
                if ($appcode) {
                    $appcode_user = $appcode->users()->where('user_id', $user->id)->first();
                    if ($appcode_user) {
                        $appcode_user->increment('number');
                    } else {
                        $appcode->users()->create(['user_id' => $user->id, 'is_new' => $is_new]);
                    }
                }
            }
        }
        return \Auth::fromUser($user);
    }

    public function walk()
    {
        $miniProgram = app('wechat')->mini_program;
        $code = $this->request->input('code');
        $data = $this->request->input('data');
        $iv = $this->request->input('iv');
        if ($code && $data && $iv) {
            $session = $miniProgram->sns->getSessionKey($code);
            $info = $miniProgram->encryptor->decryptData($session['session_key'], $iv, $data);
            if ($info) return $info;
            return $this->errorUnauthorized('no data');
        }
        return $this->errorUnauthorized('error');
    }

    public function oauth()
    {
        $openid = $this->request->input('openid');
        $preappid = $this->request->input('preappid');
        $appid = $this->request->input('appid');
        $target = $this->request->get('target');
        $hash = $this->request->get('hash');
        $oauth = getApp()->oauth;
        if (!$this->request->has('code')) {
            $scope = !empty($openid) || empty($appid) ? 'snsapi_userinfo' : 'snsapi_base';
            return $oauth->scopes([$scope])->redirect($this->request->fullUrl() . '&preappid=' . $appid);
        }
        $cxWechat = Wechat::find(1);
        $appid = $preappid;
        $pos = strpos($target, "?");
        $target = $pos !== false ? substr($target, 0, $pos) . '/' : $target;
        $wxUserInfo = $oauth->user()->getOriginal();
        if (empty($appid)) {
            $appid = $cxWechat->appid;
            $openid = $wxUserInfo['openid'];
        }
        if (isset($wxUserInfo['scope'])) {
            $correlation = UserOpenidCorrelation::where(['appid' => $appid, 'cx_openid' => $wxUserInfo['openid']])->first();
            $gzhUser = User::where('openid', $correlation['gzh_openid'])->first();
            $this->enableGzh(Wechat::where('appid', $appid)->first(), $correlation['gzh_openid']);
            if ($cxWechat->appid != $appid) {
                try {
                    $wxUserInfo = getApp($appid)->user->get($correlation['gzh_openid']);
                    getUser($wxUserInfo, Wechat::where('appid', $appid)->first());
                } catch (\Exception $exception) {}
            }
        } else {
            if (empty($openid) || $openid == 'null') {
                $appid = $cxWechat->appid;
                $openid = $wxUserInfo['openid'];
            }
            $cxUser = getUser($wxUserInfo, $cxWechat);
            $middleUser = User::where(['is_mini_user' => 1, 'unionid' => $cxUser->unionid])->where('id', '<>', $cxUser->id)->first();
            $miniUserId = $cxUser->id;
            if (empty($middleUser)) {
                $cxUser->update(['is_mini_user' => 1, 'last_appid' => $appid, 'last_openid' => $openid]);
            } else {
                $cxUser->update(['mini_user_id' => $middleUser->id]);
                $middleUser->update(['last_appid' => $appid, 'last_openid' => $openid]);
                $miniUserId = $middleUser->id;
            }
            if ($openid != $wxUserInfo['openid']) {
                $gzhUserInfo = array_diff_key($cxUser->toArray(), ['openid' => '']);
                $gzhUserInfo = ['mini_user_id' => $miniUserId, 'is_mini_user' => 0,] + $gzhUserInfo;
                $gzhUser = User::updateOrCreate(['openid' => $openid], $gzhUserInfo);
                $gzhWechat = Wechat::where('appid', $appid)->first();
                $apiUserInfo = getApp($appid)->user->get($openid);
                $wechatData = ['subscribe' => $apiUserInfo['subscribe'], 'subscribe_time' => $apiUserInfo['subscribe_time'], 'openid' => $openid];
                if (WechatUser::where('openid', $openid)->doesntExist()) {
                    \DB::table('wechat_user')->insert(array_merge($wechatData, ['user_id' => $gzhUser->id, 'wechat_id' => $gzhWechat->id]));
                } else {
                    \DB::table('wechat_user')->where('openid', $openid)->update(array_merge($wechatData, ['user_id' => $gzhUser->id, 'wechat_id' => $gzhWechat->id]));
                }
            } else {
                $gzhUser = $cxUser;
            }
            UserOpenidCorrelation::updateOrCreate(['appid' => $appid, 'cx_openid' => $cxUser->openid], ['appid' => $appid, 'gzh_openid' => $openid, 'cx_openid' => $cxUser->openid,]);
            $wechatUser = WechatUser::where(['openid' => $openid])->first();
            if (!empty($wechatUser) && $wechatUser->subscribe == 1) {
                try {
                    $this->joinTagUser(getApp($appid), $openid);
                } catch (\Exception $exception) {
                    \DB::table('wechat_user')->where('openid', $openid)->update(['subscribe' => 0]);
                }
            }
            $this->enableGzh(Wechat::where('appid', $appid)->first(), $openid);
        }
        if ($appid == 'wxe2eabd694a9f7a94' && !empty($gzhUser->unionid) && !empty($gzhUser->mini_user_id)) {
            $redis = app('redis');
            $redisKey = 'mornight:tmp:importModubusUserInfo';
            if (!$redis->hexists($redisKey, $gzhUser->id)) {
                dispatch((new \App\Jobs\importModubusUserInfo($gzhUser->mini_user_id, $gzhUser->id))->onQueue('syncRegisterUser'));
                $redis->hset($redisKey, $gzhUser->id, date('Y-m-d H:i:s'));
            }
        }
        return redirect()->to($target . '?token=' . \Auth::fromUser($gzhUser) . '#' . $hash);
    }

    public function jssdk()
    {
        $url = $this->request->input('url');
        $js = app('wechat')->js;
        $js->setUrl($url);
        return $js->config([
            'checkJsApi', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'closeWindow', 'chooseWXPay', 'scanQRCode',
            'chooseImage', 'uploadImage', 'previewImage', 'downloadImage', 'getLocation'], false);
    }

    public function refresh()
    {
        try {
            return \Auth::refresh();
        } catch (\Exception $e) {
            return $this->errorUnauthorized($e->getMessage());
        }
    }

    public function image()
    {
        $url = $this->request->get('url');
        if ($url) {
            try {
                $client = new \GuzzleHttp\Client(['verify' => false]);  //忽略SSL错误
                return $client->get($url);
            } catch (\Exception $exception) {
                return '';
            }
        }
    }

    public function switchGzh()
    {
        $appid = $this->request->input('appid');
        $openid = $this->request->input('openid');
        if (!empty($appid) && !empty($openid)) {
            UserOpenidCorrelationMini::updateOrCreate(['appid' => $appid, 'mini_openid' => $this->user()->openid], [
                'appid' => $appid,
                'gzh_openid' => $openid,
                'mini_openid' => $this->user()->openid,
            ]);
            $app = getApp($appid);
            $this->joinTagUser($app, $openid);
        }
    }
}