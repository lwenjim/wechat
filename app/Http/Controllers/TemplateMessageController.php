<?php
/*
|--------------------------------------------------------------------------
| 前台调用模板消息
|--------------------------------------------------------------------------
|
| author: ygq
| time: 20180703
| desc: 
|
*/
namespace App\Http\Controllers;

use DB;

class TemplateMessageController extends Controller{

	public function main(){

		$data = $this->request->input();

		$toUsers = DB::select('select user.openid from user,user_remind where user.id = user_remind.user_id');

		foreach ($toUsers as $key => $value) {
			sendTplMsg($data['template_id'], $data['url'], $data['word'], $value->openid, 1);
		}

        return $this->created();

	}

}