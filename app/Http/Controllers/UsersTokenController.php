<?php

namespace App\Http\Controllers;

use App\Models\TokenUsers;
use App\Models\Wechat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UsersTokenController extends Controller
{
    private static $salt = "userloginregister";
    private static $prePhoneKey = 'mornight:sms:aliyun:' ;

    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $redisKeyFailCount = 'userLogin:'.$username;
        $redis = app('redis');

        if (empty($username) || empty($password)) {
            $this->errorBadRequest('登录信息不完整,请输入用户名和密码');
        }
        if ($redis->get($redisKeyFailCount) > 5) {
            $this->errorBadRequest("登录失败次数超过5次");
        }
        $user = TokenUsers::where(['username' => $username, 'password' => sha1(static::$salt . $password)])->first();
        if (empty($user)) {
            $redis->incr($redisKeyFailCount);
            $redis->expire($redisKeyFailCount, remainSecondsForToday());
            $this->errorBadRequest('用户名或密码不正确,登录失败');
        }

        $token = str_random(60);
        $user->api_token = $token;
        $user->save();
        return $user->api_token;
    }

    public function register(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $code = $request->input('code');
        $openid = $request->input('openid');
        $invitedcode = $request->input('invitedcode');

        if (empty($username) || empty($password) || empty($code)) {
            $this->errorBadRequest('请输入完整用户信息');
        }
        if (!preg_match("/^1[34578]{1}\d{9}$/", $username)) {
            $this->errorBadRequest('用户名必须为手机号');
        }
        $key = static::$prePhoneKey . $username;
        $redis = app('redis');
        if (!$redis->exists($key)) {
            $this->errorBadRequest('验证码已过期');
        }
        if ($code != $redis->get($key)) {
            $this->errorBadRequest('验证码不正确');
        }
        if (TokenUsers::where('username', $username)->count() > 0) {
            $this->errorBadRequest('该用户已注册');
        }
        $exists = TokenUsers::where('invitecode', $invitedcode)->exists();
        if (!empty($invitedcode) && (strlen($invitedcode) != 4 || !$exists && substr($invitedcode, 0, 2) != 'YR')) {
            $this->errorBadRequest('无效邀请码');
        }
        if (static::validatePasswordText($password)) {
            $this->errorBadRequest('密码长度6-18,必须含有大、小写字母以及数字');
        }
        $user = new TokenUsers;
        $user->username = $username;
        $user->password = sha1(static::$salt . $password);
        $user->phone = $user->username;
        $user->api_token = str_random(60);
        $user->invitedcode = $invitedcode ?: '';
        $user->invitecode = generalInviteCode();
        $user->openid = $openid;
        if (!$user->save()) {
            $this->errorBadRequest('用户注册失败');
        }
        $redis->delete($key);
        $this->noContent();
    }

    public static function validatePasswordText($password)
    {
        return !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || strlen($password) > 18 || strlen($password) < 6;
    }

    public function changePasswd()
    {
        $oldPassword = $this->request->input('oldPassword');
        $newPassword = $this->request->input('newPassword');
        $username = $this->user()->username;
        $redisKeyChangePasswordFailCount = 'userChangePassword:' . $username;
        $redis = app('redis');

        if ($redis->get($redisKeyChangePasswordFailCount) > 5) {
            $this->errorBadRequest("密码错误次数超过5次");
        }
        $user = TokenUsers::where(['username' => $username, 'password' => sha1(static::$salt . $oldPassword)])->first();
        if (empty($user)) {
            $redis->incr($redisKeyChangePasswordFailCount);
            $redis->expire($redisKeyChangePasswordFailCount, remainSecondsForToday());
            $this->errorBadRequest('密码不正确');
        }
        if (static::validatePasswordText($newPassword)) {
            $this->errorBadRequest('密码长度6-18,必须含有大、小写字母以及数字');
        }
        $user->update([
            'password' => sha1(static::$salt . $newPassword),
            'api_token' => str_random(60)
        ]);
        $this->noContent();
    }

    public function findBackPasswd(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $code = $request->input('code');
        $key = static::$prePhoneKey . $username;
        $redisKeyFailCount = 'userLogin:'.$username;
        $redisKeyChangePasswordFailCount = 'userChangePassword:' . $username;
        $redis = app('redis');

        if (empty($username) || empty($code)) {
            $this->errorBadRequest('验证码错误');
        }
        if (!preg_match("/^1[34578]{1}\d{9}$/", $username)) {
            $this->errorBadRequest('用户名必须为手机号');
        }
        if (!$redis->exists($key)) {
            $this->errorBadRequest('验证码已过期');
        }
        if ($code != $redis->get($key)) {
            $this->errorBadRequest('验证码不正确');
        }
        if (TokenUsers::where('username', $username)->doesntExist()) {
            $this->errorBadRequest('该用户不存在');
        }
        if (static::validatePasswordText($password)) {
            $this->errorBadRequest('密码长度6-18,必须含有大、小写字母以及数字');
        }
        $redis->delete($key);
        $redis->delete($redisKeyFailCount);
        $redis->delete($redisKeyChangePasswordFailCount);
        $user = TokenUsers::where(['username' => $username])->first();
        $user->update([
            'password' => sha1(static::$salt . $password),
            'api_token' => str_random(60)
        ]);
        $this->noContent();
    }

    public function info()
    {
        return Auth::user();
    }

    public function logout()
    {
        TokenUsers::where('id', '=', Auth::user()->id)->update(['api_token' => str_random(60)]);
        $this->noContent();
    }

    public function applist()
    {
        $appids = Auth::user()->appid;
        if (empty($appids)) {
            $this->errorBadRequest('appids 为空');
        }
        return Wechat::whereIn('appid', explode('|', $appids))->where(['status' => 1])->get();
    }

    public function switch_curapp($appid)
    {
        if (!in_array($appid, $appids = explode('|', Auth::user()->appid))) {
            $this->errorBadRequest('appids 为空');
        }
        TokenUsers::where('id', '=', Auth::user()->id)->update(['cur_appid' => $appid]);
        return $appid;
    }

    public function updateInfo()
    {
        $data = $this->request->only(['username', 'nickname', 'phone', 'password', 'email', 'industry', 'company','headimg','job']);
        TokenUsers::where(['id' => $this->user()->id])->update($data);
        return $this->noContent();
    }

    public function appRemove($appid)
    {
        $appids = explode('|', $this->user()->appid);
        $appids = array_unique($appids);
        if (in_array($appid, $appids)) {
            $appids = array_diff($appids, [$appid]);
        }
        $appids = implode('|', $appids);
        $user = $this->user();
        $user->appid = $appids;
        $user->save();
        Wechat::where('appid', $appid)->update(['status' => 0]);
        return $this->noContent();
    }

    public function sendMsg($type)
    {
        $phone = $this->request->input('phone');
        if (!preg_match("/^1[34578]{1}\d{9}$/", $phone)) {
            $this->errorBadRequest('手机格式有误');
        }
        $phone_key = static::$prePhoneKey . $phone;
        $redis = app('redis');
        if ($redis->exists($phone_key)) {
            $this->errorBadRequest('短信已经发送');
        }
        if (TokenUsers::where('username', $phone)->count() > 0 && $type == 1) {
            $this->errorBadRequest('该用户已注册');
        }
        $ret = smsSend($phone);
        if (!$ret) {
            $this->errorBadRequest('短信发送失败');
        }
        $redis->setex($phone_key, 300, $ret);
        $this->noContent();
    }

    public function bindQrcode()
    {
        $app = getApp();
        $qrcode = $app->qrcode;
        $result = $qrcode->temporary('bindQrcode' . $this->user()->id, 120);
        return ['url' => $qrcode->url($result->ticket)];
    }

    public function qccodeLogin()
    {
        if ($this->request->isMethod('post')) {
            $code = $this->request->input('code');
            $data = Cache::get('wechat:qrcode:pingtai:' . $code);
            if ($data) {
                return json_decode($data, true);
            } else {
                return $this->noContent();
            }
        } else {
            $code = mt_rand(50000001, 99999999);
            $qrcode = getApp()->qrcode;
            $result = $qrcode->temporary($code, 120);
            return ['url' => $qrcode->url($result->ticket), 'code' => $code];
        }
    }
}