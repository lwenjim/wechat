<?php
/**
 * Created by PhpStorm.
 * User: Mediabook
 * Date: 2018/10/22
 * Time: 14:39
 */

namespace App\Console\Commands\Shell;
use App\Jobs\Sign;
use App\Models\MsgSession;
use App\Models\User;
use App\Models\UserSign;
use App\Models\UserSignComment;
use App\Models\WeChatQrcodeUser;
use App\Models\WechatUser;
use function foo\func;
use function getApp;
use Illuminate\Console\Command;
use DB;
use Mockery\Exception;
use function print_r;
use App\Console\Kernel;
use const true;
use Intervention\Image\ImageManagerStatic as Image;

use App\Models\Wechat;
use App\Models\Achievement;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Core\Exceptions\HttpException;

class batchBuka
{
    public static function run()
    {
        $userIds = WechatUser::where(['wechat_id' => 83, 'subscribe' => 1])->pluck('user_id');
        $miniUserIds = User::whereIn('id', $userIds)->where('mini_user_id', '>', 0)->pluck('mini_user_id');
        print_r($miniUserIds);exit;//3473029
        User::whereIn('id', [3472417])->chunk(200, function ($miniUsers) {
            foreach ($miniUsers as $miniUser) {
                for ($i = 2; $i >= 0; $i--) {
                    $theDate = date('Y-m-d', strtotime("-{$i} day"));
                    if (UserSign::where(['user_id' => $miniUser->id, 'date' => $theDate])->exists()) continue;
                    $data['user_id'] = $miniUser->id;
                    $data['user_ip'] = '127.0.0.1';
                    $data['date'] = $theDate;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $data['hour'] = 11;
                    $data['appid'] = 'wx2dc98b8c0f486690';
                    $data['wechat_id'] = 83;
                    $data['day'] = $miniUser->getDays($theDate);
                    UserSign::create($data);
                    echo $miniUser->id, $theDate, "\n";
                }
                $dateList = \App\Models\UserSign::where('user_id', $miniUser->id)->orderBy('date', 'desc')->pluck('date')->toArray();
                $dateList = array_values(array_unique($dateList));
                $len = count($dateList);
                for ($i = 0; $i < $len - 1; $i++) {
                    if (date('Y-m-d', strtotime($dateList[$i]) - 86400) != $dateList[$i + 1]) {
                        break;
                    }
                }
                if ($miniUser->day != $i + 1) {
                    echo $miniUser->id, "\t", $miniUser->day, "\t", ($i + 1), "\n";
                }
                \App\Models\User::find($miniUser->id)->update(['day' => $i + 1]);
                UserSign::where(['user_id' => $miniUser->id, 'date' => date('Y-m-d')])->update(['day' => $i + 1]);


//                for ($i = 19; $i >= 0; $i--) {
//                    changeCoin($miniUser->id, 100, 'sign', $userSign['id'], '打卡送原力');
//                }
//                echo "2100 原力 ";
//                if($miniUser->day<100){
//                    $diamond = 20;
//                }elseif($miniUser->day<300){
//                    $diamond = 50;
//                }elseif($miniUser->day<600){
//                    $diamond = 100;
//                }else{
//                    $diamond = 200;
//                }
//                incrementBlueDiamond(83, $miniUser->id, $diamond, '打卡获得蓝钻');
//                echo "{$diamond} 蓝钻\t";
            }
        });
    }
}