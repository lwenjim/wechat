<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\Wechat;
use App\Models\WechatUser;
use App\Transformers\TaskTransformer;
use App\Transformers\UserTransformer;
use App\Models\Activity;
use App\Models\ActivityRead;
use App\Transformers\ActivityTransformer;
use App\Models\Achievement;


class UserController extends Controller
{
    public function getUserById($id)
    {
        $user = User::select('id', 'unionid', 'nickname', 'headimgurl', 'day', 'coin', 'group_id', 'mini_user_id', 'is_mini_user', 'created_at')->where('id', $id)->first();
        if (!$user) {
            return $this->errorNotFound();
        }
        $userInfo = $this->item($user, new UserTransformer());
        $arr = [];
        foreach ($user->getLastActiveGzhUser(false) as $k => $v) {
            if (is_bool($v)) {
                break;
            }
            $arr[] = $v->wechats()->first();
        }
        $userInfo['data']['wechats']['data'] = $arr;
        $userInfo['data']['wechats']['meta']['total'] = count($arr);
        return $userInfo;
    }

    public function get()
    {
        $user = $this->user()->switchToMiniUser();
        $user = User::select('id', 'country', 'province', 'city', 'openid', 'unionid', 'nickname', 'headimgurl', 'day', 'coin', 'group_id', 'mini_user_id', 'is_mini_user', 'created_at', 'last_appid', 'blue_diamond', 'sex')->where('id', $user->id)->first();
        if (!$user) {
            return $this->errorNotFound();
        }
        $userInfo = $this->item($user, new UserTransformer());
        $arr = [];
        foreach ($user->getLastActiveGzhUser(false) as $k => $v) {
            if (is_bool($v)) {
                break;
            }
            $arr[] = $v->wechats()->first();
        }
        $userInfo['data']['wechats']['data'] = $arr;
        $userInfo['data']['wechats']['meta']['total'] = count($arr);
        $userInfo['last_app_info'] = Wechat::where(['appid' => $user->last_appid])->select('id', 'name', 'owner', 'headimgurl')->first();
        $userInfo['gzhOpenid'] = $this->user()->openid;
        $userInfo['gzhUserId'] = $this->user()->id;
        $userInfo['wechatuser'] = $this->user()->wechatUser()->first();

        if (crc32(microtime(true)) % 2 == 0) {
            $wechatId = WechatUser::where('openid', $this->user()->openid)->value('wechat_id');
            if ($wechatId > 0) {
                $appid = Wechat::find($wechatId)->appid;
                getUser(getApp($appid)->user->get($this->user()->openid));
            }
        }
        return $userInfo;
    }

    public function put()
    {
        $data = $this->request->input();
        if (isset($data['tags']) && !empty($data['tags'])) {
            $this->user()->catalogs()->sync(explode(',', $data['tags']));
        }
        if (isset($data['wechat']) && !empty($data['wechat'])) {
            $this->user()->wechats()->updateExistingPivot($data['wechat']['id'], ['is_default' => $data['wechat']['is_default']]);
        }
        if ($update = array_except($data, ['id', 'tags', 'wechat'])) {
            $this->user()->update($update);
        }
        return $this->created();
    }

    public function task($id = 0)
    {
        if ($this->request->isMethod('post') || $this->request->isMethod('put')) {
            $data = $this->request->input();
            $validator = \Validator::make($data, [
                'name' => 'required|max:255'
            ]);
            if ($validator->fails()) {
                return $this->errorBadRequest($validator->messages());
            }
            $data['origin'] = 0;
            $Task = Task::updateOrCreate(['id' => $id], array_except($data, ['users', 'remind', 'sort', 'star']));
            if ($Task) {
                $user_id = $this->user()->id;
                if ($id == 0) {
                    $data['users'] = [$user_id => ['owner' => 1]];
                }
                if (isset($data['users']) && is_array($data['users'])) {
                    $Task->users()->sync($data['users']);
                }
                if (isset($data['remind']) || isset($data['sort']) || isset($data['star'])) {
                    if (isset($data['remind'])) {
                        $Task->users()->updateExistingPivot($user_id, ['remind' => json_encode($data['remind'])]);
                    } else {
                        $Task->users()->updateExistingPivot($user_id, array_only($data, ['sort', 'star']));
                    }
                }
            }
            return $this->created();
        } elseif ($this->request->isMethod('delete')) {
            $Task = Task::findOrFail($id);
            $Task->delete();
            return $this->noContent();
        } else {
            $Task = Task::findOrFail($id);
            return $this->item($Task, new TaskTransformer());
        }
    }

    public function taskComment($id, $comment_id = 0)
    {
        if ($this->request->isMethod('post')) {
            $data = $this->request->input();
            $data['user_id'] = $this->user()->id;
            $validator = \Validator::make($data, [
                'content' => 'required'
            ]);
            if ($validator->fails()) {
                return $this->errorBadRequest($validator->messages());
            }
            $Task = Task::findOrFail($id);
            $Task->comments()->create($data);
            return $this->created();
        } else {
            $Task = Task::findOrFail($id);
            $TaskComment = $Task->comments()->findOrFail($comment_id);
            $TaskComment->delete();
            return $this->noContent();
        }
    }

    public function taskUser($id)
    {
        $Task = Task::findOrFail($id);
        if ($this->request->isMethod('post')) {
            $user_id = $this->user()->id;
            if ($Task->users()->where('id', $user_id)->count()) {
                return $this->errorBadRequest('您已经加入了');
            } else {
                $Task->users()->attach($user_id);
            }
            return $this->noContent();
        } else {
            return $this->item($Task, new TaskTransformer());
        }
    }

    public function activity($type = 0, $id = '')
    {
        $activities = Activity::select('id', 'type', 'title', 'short_title', 'outer_href', 'cover', 'image', 'tag', 'tag_color', 'top', 'begin_time', 'end_time', 'created_at')->withCount(['read' => function ($query) {
            $query->where('user_id', $this->user()->id);
        }]);
        if ($id) {
            $activity = $activities->where('id', $id)->first();
            if ($activity->read_count == 0) {
                ActivityRead::create(['activity_id' => $id, 'user_id' => $this->user()->id]);
            }
            return $this->item($activity, new ActivityTransformer());
        } else {
            $activities = $activities->when($type, function ($query) use ($type) {
                return $query->where('type', $type);
            });
        }
        return $this->paginator($activities->orderBy('top', 'desc')->orderBy('created_at', 'desc')->paginate(), new ActivityTransformer());
    }

    public function unread()
    {
        $type = $this->request->get('type', 0);
        $user_id = $this->user()->id;
        $activity_count = Activity::select('id')->when($type, function ($query) use ($type) {
            return $query->where('type', $type);
        })->whereDoesntHave('read', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->count();
        if ($type == 0) {
            $activity_count += $this->user()->msgs()->where('is_read', 0)->count();
        }
        return $activity_count;
    }

    public function h5mission()
    {
        $user = $this->user();
        if ($this->request->isMethod('post')) {
            $type = $this->request->input('type');
            $result = 0;
            if ($type == 'today') {//完成今日任务
                $data = getMission($user);
                if ($data['sign'] >= 1
                    && $data['moment'] >= 1
                    && $data['like'] >= 1
                    && $user->missions()->where('type', 'today')->where('mark', date('Y-m-d'))->count() == 0
                ) {
                    changeCoin($user->id, 100, 'today', 1, '完成' . date('Y-m-d') . '日任务送100原力') && sendMsg($user->id, '原力消息提醒', 'today', '恭喜您完成' . date('Y-m-d') . '日任务，送您100原力！');
                    $user->missions()->create([
                        'type' => 'today',
                        'mark' => date('Y-m-d'),
                    ]);
                    // sendTplMsg('dG9HxopfLmHHkXRkyRGhltT1qIR-_x3cMznB64m4B4o', 'pages/index/index', ['keyword1' => '每日任务', 'keyword2' => '当天任务已经完成啦，获得200原力'], $user->openid);
                    $result = 1;
                }
            } elseif ($type == 'sign21') {//累计打卡21天
                $day = $user->day - $user->signs()->where('date', '2017-07-01')->value('day');
                if ($day > 0 && $day % 21 == 0 && $user->missions()->where('type', 'sign21')->where('mark', date('Y-m-d'))->count() == 0) {
                    changeCoin($user->id, 10, 'sign21', 21, '连续打卡21天送10原力') && sendMsg($user->id, '原力消息提醒', 'sign', '恭喜您连续打卡21天，送您10原力！');
                    $user->missions()->create([
                        'type' => 'sign21',
                        'mark' => date('Y-m-d'),
                    ]);
                    $result = 1;
                }
            } elseif ($type == 'invite5') {//累计邀请好友
                $invite = $user->invites()->where('created_at', '>', '2017-07-01 00:00:00')->count();
                if ($invite > 0 && $invite % 5 == 0 && $user->missions()->where('type', 'invite5')->where('mark', $invite)->count() == 0) {
                    changeCoin($user->id, 10, 'invite5', 5, '完成累计邀请' . $invite . '个好友送10原力') && sendMsg($user->id, '原力消息提醒', 'invite', '完成累计邀请' . $invite . '个好友,恭喜您获得10原力');
                    $user->missions()->create([
                        'type' => 'invite5',
                        'mark' => $invite,
                    ]);
                    $result = 1;
                }
            } else {
                $result = 0;
            }
            return $result;
        } else {
            $data['today'] = getMission($user);
            unset($data['walk']);
            $data['achieve']['today'] = $user->missions()->where('type', 'today')->where('mark', date('Y-m-d'))->count();

            $sign = intval($user->day - $user->signs()->where('date', '2017-07-01')->value('day'));
            $data['achieve']['sign'] = $sign > 0 ? $sign : 0;
            if ($sign > 0 && $sign % 21 == 0) {
                if ($user->missions()->where('type', 'sign21')->where('mark', date('Y-m-d'))->count() == 0) {
                    $data['achieve']['sign21'] = 0;
                } else {
                    $data['achieve']['sign21'] = 1;
                }
            } else {
                $data['achieve']['sign21'] = -1;
            }

            $invite = $user->invites()->where('created_at', '>', '2017-07-01 00:00:00')->count();
            $data['achieve']['invite'] = $invite;
            if ($invite > 0 && $invite % 5 == 0) {
                if ($user->missions()->where('type', 'invite5')->where('mark', $invite)->count() == 0) {
                    $data['achieve']['invite5'] = 0;
                } else {
                    $data['achieve']['invite5'] = 1;
                }
            } else {
                $data['achieve']['invite5'] = -1;
            }
            return $data;
        }
    }

    public function mission()
    {
        $user = $this->user();
        if ($this->request->isMethod('post')) {
            $type = $this->request->input('type');
            $result = 0;
            if ($type == 'today') {//完成今日任务
                $data = getMission($user);
                if ($data['sign'] >= 1
                    && $data['walk'] >= 1
                    && $data['give'] >= 1
                    && $data['moment'] >= 1
                    && $data['like'] >= 1
                    && $user->missions()->where('type', 'today')->where('mark', date('Y-m-d'))->count() == 0
                ) {
                    changeCoin($user->id, 100, 'today', 1, '完成' . date('Y-m-d') . '日任务送100原力') && sendMsg($user->id, '原力消息提醒', 'today', '恭喜您完成' . date('Y-m-d') . '日任务，送您100原力！');
                    $user->missions()->create([
                        'type' => 'today',
                        'mark' => date('Y-m-d'),
                    ]);
                    sendTplMsg('dG9HxopfLmHHkXRkyRGhltT1qIR-_x3cMznB64m4B4o', 'pages/index/index', ['keyword1' => '每日任务', 'keyword2' => '当天任务已经完成啦，获得200原力'], $user->openid);
                    $result = 1;
                }
            } elseif ($type == 'sign21') {//累计打卡21天
                $day = $user->day - $user->signs()->where('date', '2017-07-01')->value('day');
                if ($day > 0 && $day % 21 == 0 && $user->missions()->where('type', 'sign21')->where('mark', date('Y-m-d'))->count() == 0) {
                    changeCoin($user->id, 10, 'sign21', 21, '连续打卡21天送10原力') && sendMsg($user->id, '原力消息提醒', 'sign', '恭喜您连续打卡21天，送您10原力！');
                    $user->missions()->create([
                        'type' => 'sign21',
                        'mark' => date('Y-m-d'),
                    ]);
                    $result = 1;
                }
            } elseif ($type == 'invite5') {//累计邀请好友
                $invite = $user->invites()->where('created_at', '>', '2017-07-01 00:00:00')->count();
                if ($invite > 0 && $invite % 5 == 0 && $user->missions()->where('type', 'invite5')->where('mark', $invite)->count() == 0) {
                    changeCoin($user->id, 10, 'invite5', 5, '完成累计邀请' . $invite . '个好友送10原力') && sendMsg($user->id, '原力消息提醒', 'invite', '完成累计邀请' . $invite . '个好友,恭喜您获得10原力');
                    $user->missions()->create([
                        'type' => 'invite5',
                        'mark' => $invite,
                    ]);
                    $result = 1;
                }
            } else {
                $result = 0;
            }
            return $result;
        } else {
            $data['today'] = getMission($user);
            $data['achieve']['today'] = $user->missions()->where('type', 'today')->where('mark', date('Y-m-d'))->count();

            $sign = intval($user->day - $user->signs()->where('date', '2017-07-01')->value('day'));
            $data['achieve']['sign'] = $sign > 0 ? $sign : 0;
            if ($sign > 0 && $sign % 21 == 0) {
                if ($user->missions()->where('type', 'sign21')->where('mark', date('Y-m-d'))->count() == 0) {
                    $data['achieve']['sign21'] = 0;
                } else {
                    $data['achieve']['sign21'] = 1;
                }
            } else {
                $data['achieve']['sign21'] = -1;
            }

            $invite = $user->invites()->where('created_at', '>', '2017-07-01 00:00:00')->count();
            $data['achieve']['invite'] = $invite;
            if ($invite > 0 && $invite % 5 == 0) {
                if ($user->missions()->where('type', 'invite5')->where('mark', $invite)->count() == 0) {
                    $data['achieve']['invite5'] = 0;
                } else {
                    $data['achieve']['invite5'] = 1;
                }
            } else {
                $data['achieve']['invite5'] = -1;
            }
            if ($data['today']['sign'] > 0 && $data['today']['walk'] > 0 && $data['today']['moment'] > 0 && $data['today']['like'] > 0) {
                $data['today']['give'] = 1;
            }
            return $data;
        }
    }

    public function tplmsg($id)
    {
        app('redis')->setex('mornight:active:miniprogram:tplmsg:' . $this->user()->openid . ':' . $id, 604800, 1);
        return $this->created();
    }

    public function config($type = 'remind')
    {
        $user = $this->user()->switchToMiniUser();
        if ($this->request->isMethod('get')) {
            return $user->config;
        }
        $config = $user->config;
        $config = $config + ['remain_evening_read' => 1, 'remain_evening_read_time' => '20:00'];
        if (in_array($type, ['remain_evening_read_time', 'sign_time'])) {
            $config[$type] = $this->request->input('time');
        } else {
            $config[$type] = empty($config[$type]) ? 1 : 0;
        }
        $user->update(['config' => $config]);
        return $this->created();
    }

    public function stat()
    {
        $data = $this->request->input();
        if (isset($data['path'])) {
            $data['user_ip'] = $this->request->ip();
            $this->user()->stats()->create($data);
        }
        return $this->created();
    }


    public function phone()
    {
        $data = $this->request->input();
        if ($this->user()->phones()->where($data)->count() == 0) {
            $this->user()->phones()->create($data);
        }
        return $this->created();
    }

    public function feedback()
    {
        $data = $this->request->input();
        $this->user()->feedback()->create($data);
        return $this->created();
    }

    public function getCardInfo($userid = 0)
    {
        $user = $userid > 0 ? User::find($userid) : $this->user();
        /* $wechatId = $user->wechatUser()->value('wechat_id');
         $wechat = Wechat::find($wechatId);*/
        if ($user) {
            $last_appid = $user->last_appid;
        } else {
            $last_appid = 'wxa7852bf49dcb27d7';
        }
        $wechat = Wechat::where('appid', $last_appid)->first();
        $type = 'primary';
        $date = date('Y-m-d');
        $achieveModel = Achievement::select('image', 'font', 'width', 'height', 'content', 'appid')->whereIn('appid', [$wechat->appid, config('wechat.app_id')])->where(['default' => 0, 'type' => $type, 'date' => $date])->get();
        if (count($achieveModel) == 2) {
            foreach ($achieveModel as $model) {
                if ($model->appid != $wechat->appid) continue;
                $achieveModel = $model;
                break;
            }
        } else {
            $achieveModel = $achieveModel[0];
        }
        $content = $achieveModel->content;
        $result = '';
        if (isset($content['qrcode'])) {
            $qrcode = getApp($wechat->appid)->qrcode;
            if ($wechat->type == 2) {
                $result = $qrcode->temporary($user->id, 7 * 24 * 3600);
            }
        }
        return ['result' => $result, 'achieve' => $achieveModel, 'qrcode' => $wechat->qrcode];
    }
}