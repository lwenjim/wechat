<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class ActivityWechat extends Model
{
    use CascadesDeletes;
    protected $table = 'activity_wechat';
    protected static $page_size = 10;

    //绑定活动和公众号
    static public function bindActivity($activity_id,$wx_id){
        return self::firstOrCreate(['activity_id'=>$activity_id,'wx_id'=>$wx_id,'status'=>1]);
    }

    //删除活动
    static public function deleteActivity($activity_id,$wx_id){
        return self::where(['activity_id'=>$activity_id,'wx_id'=>$wx_id,'status'=>1])->update(['status'=>0]);
    }

    //通过公众号获取列表
    static public function getListByWxid($wx_id,$status = 1){
        return self::where(['wx_id'=>$wx_id,'status'=>$status])->paginate(self::$page_size);
    }

}
