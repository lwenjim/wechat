<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/2 0002
 * Time: 16:38
 */

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Wechat;
use App\Transformers\UserTransformer;


class UserCenterController extends Controller
{
    public function userList()
    {
        $params = $this->request->only(['nickname', 'active']);
        $wechat = Wechat::where(['appid' => $this->user()->cur_appid])->first();
        $userModel = $wechat->users()->withCount('invites');
        $activeUserList = getActiveUser($this->user()->cur_appid);
//        dd($activeUserList);
        if (isset($params['active']) && $params['active']) {
            $userModel = $userModel->whereIn('openid', $activeUserList);
        }
        if (isset($params['nickname']) && $params['nickname']) {
            $userModel = $userModel->where('nickname', 'like', $params['nickname'] . '%');
        }
        list($by, $order) = explode(',', $this->request->get('order', 'created_at,desc'));
        if (!in_array($by, ['created_at', 'coin', 'day', 'blue_diamond']) || !in_array($order, ['desc', 'asc'])) {
            return $this->errorBadRequest('order param error');
        }
        $userModel = $userModel->wherePivot('subscribe', 1);
        $list = $userModel->OrderBy($by, $order)->paginate(15);
        $ret = $this->paginator($list, new UserTransformer());
        $ret['data'] = array_map(function ($user) use ($activeUserList) {
            $user['active'] = in_array($user['openid'], $activeUserList);
            return $user;
        }, $ret['data']);
        return $ret;
    }

    public function delete($user_id)
    {
        User::find($user_id)->delete();
        $this->noContent();
    }

    public function getPortRaitAndRegion()
    {
        return Wechat::where('appid', $this->user()->cur_appid)->select('portrait', 'region', 'summary_phone_model')->first();
    }
}