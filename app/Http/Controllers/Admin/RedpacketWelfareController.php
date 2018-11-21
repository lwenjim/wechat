<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13 0013
 * Time: 17:35
 */

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\UserRedPacketInfo;
use App\Transformers\UserRedPacketInfoTransformer;
use App\Models\UserRedPacketAdminInfo;
use DB;
use function explode;

class RedpacketWelfareController extends AdminController
{
    public function index()
    {
        $nickname = $this->request->input('nickname');
        $validate_time = $this->request->input('validate_time');
        $validate_time_flag = $this->request->input('validate_time_flag');
        $send_time_flag = $this->request->input('send_time_flag');
        $builder = UserRedPacketInfo::select('user_id', 'openid', 'receive_time', 'validate_time', 'send_time', 'appid','validate_secret');
        if (!empty($nickname) && !empty($userIds = User::where('nickname', 'like', $nickname . '%')->pluck('id')->toArray())) {
            $builder = $builder->whereIn('user_id', $userIds);
        }
        if (isset($validate_time_flag)) {
            $builder = $builder->whereNull('validate_time');
        }else{
            if(!empty($validate_time)){
                $builder = $builder->where(function ($query) use($validate_time){
                    $validate_time = explode('|', $validate_time);
                    if (isset($validate_time[0])) $query->where('validate_time', '>=', $validate_time[0]);
                    if (isset($validate_time[1])) $query->where('validate_time', '<=', $validate_time[1]);
                });
            }
        }
        if (isset($send_time_flag)) {
            $builder = $builder->whereNull('send_time');
            $builder = $builder->whereNotNull('validate_time');
        }
        $list = $builder->orderBy('send_time','asc')->paginate(14);
        return $this->paginator($list, new UserRedPacketInfoTransformer());
    }

    public function updateQrCodeImg()
    {
        $qrcode = $this->request->input('qrcodeimg');
        UserRedPacketAdminInfo::updateOrCreate(['user_id' => 1], ['qrcode' => $qrcode]);
        return $this->noContent();
    }

    public function sendRedPacket($userId)
    {
        $user = User::find($userId);
        $user->redPacketUser()->updateOrCreate(['openid' => $user->openid,], ['send_time' => date('Y-m-d H:i:s')]);
        return $this->noContent();
    }

    public function generalValidateLink($openid)
    {
        $redPacketUser = UserRedPacketInfo::where('openid',$openid)->first();
        $checkCode = static::generalCode();
        $redPacketUser->update(['validate_secret'=>$checkCode]);
        return 'https://www.mornight.net/h5/#/wsok/' . substr(md5(md5($openid) . $checkCode), 10, 5);
    }

    public static function generalCode()
    {
        $charactor = 'o7cFVDQ8TqGCigUuANkeIZBx1jtv0J6KMl9L5ryp4ab3HfOWnd2XSYEPsRhzmw';
        $base62 = '';
        $int = crc32(microtime(1));
        while ($int > 0) {
            $mod = $int - floor($int / 62) * 62;
            $int = floor($int / 62);
            $mod = intval($mod);
            $base62 .= $charactor[$mod];
        }
        return str_pad($base62, 4, 'o', STR_PAD_LEFT);
    }
}