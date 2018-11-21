<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityWechat;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(){
        $method = $this->request->get('method','default');
        switch ($method){

            case 'getActivityList':
                return $this->getActivityList();
                break;
            default:
                return $this->errorBadRequest('缺少参数:method');
                break;
        }
    }

    private function getActivityList(){
        $field = ['id','title','icon','url','start','end','sort','select','status','image','created_at','updated_at'];
        $res = Activity::getActivityList($field,['status'=>1]);
        if($res){
            return $res;
        }else{
            return $this->errorBadRequest('获取可绑定的活动列表失败');
        }
    }

    private function getActivityListNow(){
        $list2 = $this->getList('now');
        if($list2){
            return $list2;
        }else{
            return $this->errorBadRequest('获取该公众号的活动列表失败');
        }
    }

    private function getActivityListBefore(){
        $list2 = $this->getList('before');
        if($list2){
            return $list2;
        }else{
            return $this->errorBadRequest('获取该公众号的活动列表失败');
        }
    }

    private function getActivityListByWxid(){
        $list2 = $this->getList();
        if($list2){
            return $list2;
        }else{
            return $this->errorBadRequest('获取该公众号的活动列表失败');
        }
    }

    private function getList($type = false){
        $wx_id = (int) $this->request->input('wx_id',0);
        if($wx_id == 0){
            return $this->errorBadRequest('缺少参数:wx_id');
        }
        $list = ActivityWechat::getListByWxid($wx_id);
        $list2 = $list->toArray();
        $ids = [];
        array_walk($list2['data'],function($v)use(&$ids){
            array_push($ids,$v['activity_id']);
        });
        $where = ['status'=>1];
        $list2['data'] = Activity::getActivity($ids,false,$where,$type);
        return $list2;
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

    private function addActivity(){
        $activity_id = (int) $this->request->input('activity_id',0);
        $wx_id = (int) $this->request->input('wx_id',0);
        if($activity_id == 0){
            return $this->errorBadRequest('缺少参数:activity_id');
        }
        if($wx_id == 0){
            return $this->errorBadRequest('缺少参数:wx_id');
        }

        $res = ActivityWechat::bindActivity($activity_id,$wx_id);
        if($res){
            return $this->created(null,'添加活动成功');
        }else{
            return $this->errorBadRequest('添加活动失败');
        }
    }

    private function deleteActivity(){
        $activity_id = (int) $this->request->input('activity_id',0);
        $wx_id = (int) $this->request->input('wx_id',0);
        if($activity_id == 0){
            return $this->errorBadRequest('缺少参数:activity_id');
        }
        if($wx_id == 0){
            return $this->errorBadRequest('缺少参数:wx_id');
        }

        $res = ActivityWechat::deleteActivity($activity_id,$wx_id);
        if($res){
            return $this->created(null,'删除活动成功');
        }else{
            return $this->errorBadRequest('删除活动失败');
        }
    }


}