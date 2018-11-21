<?php
/*
|--------------------------------------------------------------------------
| 后台设置绑定审核提醒人
|--------------------------------------------------------------------------
|
| author: ygq
| time: 20180702
| desc: 
|
*/
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Input;
use App\Models\User;
use App\Models\Wechat;
use App\Models\WechatUser;
use App\Models\UserRemind;

class UserRemindController extends AdminController
{
    //设置提醒人
    function index(){
        $uid = Input::get('uid');
        $wechatUser = WechatUser::where('user_id', $uid)->first();
        $wechatInfo = Wechat::where('id', $wechatUser->wechat_id)->first();
        if ($wechatInfo->appid != 'wxa7852bf49dcb27d7') {
        	return $this->errorBadRequest('非晨夕公众号用户！');
        }else{
        	UserRemind::updateOrCreate(['user_id'=>$uid], ['user_id'=>$uid]);
        	return $this->created();
        }
    }

    function remove(){
        $uid = Input::get('uid');
        UserRemind::where('user_id', $uid)->delete();
        return $this->noContent();
    }

}