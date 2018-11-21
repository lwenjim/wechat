<?php
namespace App\Http\Controllers;

use DB;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use App\Models\UserCoin;

class QuanticController extends Controller
{

    public function test($coin = 1000){
        return fisherMission($this->user()->switchToMiniUser()->id);
    }

    public function redirect(){
        if (Input::has('target')) {
            $target = Input::get('target');
        }else{
            return $this->errorBadRequest('跳转地址不存在。');
        }
        $target = strstr($target, 'http') ? urldecode($target) : 'http://'.urldecode($target);
        return redirect($target);
    }

	//修复mini_user_id指向自己的问题
    public function main()
    {
        return 1;

    	$users = DB::select('select id, nickname, mini_user_id,unionid,is_mini_user from user where mini_user_id = id and is_mini_user=0');
    	$count = 0;
    	foreach ($users as $key => $value) {
    		$mini_user = User::where('unionid', $value->unionid)->where('is_mini_user', 1)->first();
    		if (is_null($mini_user)) {
    			continue;
    		}else{
    			$count++;
    			User::where('id', $value->id)->update(['mini_user_id'=>$mini_user->id]);
    		}
    	}
    	echo 'success: '.$count;
    }


}