<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityAdminController extends Controller
{
    public function index(){
        $method = $this->request->get('method','default');
        switch ($method){
            //添加或编辑活动
            case 'saveActivity':
                return $this->saveActivity();
                break;
            //获取活动列表
            case 'getActivityList':
                return $this->getActivityList();
                break;
            //获取单个活动详情
            case 'getOneActivity':
                return $this->getOneActivity();
                break;
            //删除活动
            case 'deleteActivity':
                return $this->deleteActivity();
                break;
            default:
                return $this->errorBadRequest('缺少参数:method');
                break;
        }
    }

    private function getActivityList(){
        $field = ['id','title','icon','url','start','end','sort','select','status','image','created_at','updated_at'];
        $res = Activity::getActivityList($field);
        if($res){
            return $res;
        }else{
            return $this->errorBadRequest('获取后台活动列表失败');
        }
    }

    public function getOneActivity(){
        $id = (int) $this->request->input('id',0);
        if($id == 0){
            return $this->errorBadRequest('缺少参数:id');
        }
        $res = Activity::getOneActivity($id);
        if($res){
            return $res;
        }else{
            return $this->errorBadRequest('获取活动详情失败');
        }
    }

    private function saveActivity(){
        $request = $this->request->input();
        $data = isset($request['data']) ? (is_array($request['data']) ? $request['data'] : json_decode($request['data'],true)) : [];
        $validator = \Validator::make($data, [
            'title' => 'required|max:255',
            'content' => 'required',
            'start' => 'required',
            'end' => 'required',
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

    private function deleteActivity(){
        $id = (int) $this->request->input('id',0);
        if($id == 0){
            return $this->errorBadRequest('缺少参数:id');
        }
        $res = Activity::deleteActivity($id);
        if($res){
            return $this->created(null,'删除活动成功');
        }else{
            return $this->errorBadRequest('删除活动失败');
        }
    }


}