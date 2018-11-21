<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityWechat;
use Illuminate\Http\Request;

class UserH5Controller extends Controller
{
    public function index(){
        $method = $this->request->get('method','default');
        switch ($method){
            //保存用户信息
            case 'saveInfo':
                return $this->saveInfo();
                break;
            default:
                return $this->errorBadRequest('缺少参数:method');
                break;
        }
    }

    private function saveInfo(){
        $request = $this->request->input();
        $data = isset($request['data']) ? json_decode($request['data'],true) : [];
        $validator = \Validator::make($data, [
            'name' => 'required',
            'mobile' => 'required',
            'province' => 'required',
            'city' => 'required',
            'district' => 'required',
            'address' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $id = isset($data['id']) ? $data['id'] : 0 ;
        $product = Activity::saveData($data,$id);
        if($product->id){
            return $this->created(null,'保存活动成功');
        }else{
            return $this->errorBadRequest('保存活动失败');
        }
    }
}