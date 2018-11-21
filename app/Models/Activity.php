<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Activity extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['read', 'content'];
    protected $table = 'activity';
    protected static $page_size = 10;

    public function content()
    {
        return $this->hasOne(ActivityContent::class);
    }

    public function read()
    {
        return $this->hasMany(ActivityRead::class);
    }

    //添加或编辑
    static public function saveData($data,$id = 0){
        return self::updateOrCreate(['id'=>$id],$data);
    }

    //获取活动列表
    static public function getActivityList($field = false,$where = []){
        $res = self::where('status','<>',2)
            ->orderBy('sort','desc');
        if($field){
            $res = $res->select($field);
        }
        if($where){
            $res = $res->where($where);
        }
        return $res->paginate(self::$page_size);
    }

    //根据ID获取单个活动内容
    static public function getOneActivity($id){
        return self::where('id',$id)->first();
    }

    //根据 商品ID 获取多个商品
    static public function getActivity($ids,$field = false,$where = [],$type = false){
        $res = self::whereIn('id',$ids)
            ->orderBy('sort','desc');
        if($field){
            $res = $res->select($field);
        }
        if($where){
            $res = $res->where($where);
        }
        //进行中的活动
        if($type == 'now'){
            $res = $res->where('end','>=',date('Y-m-d H:i:s'));
        }
        //过期的活动
        if($type == 'before'){
            $res = $res->where('end','<=',date('Y-m-d H:i:s'));
        }
        $res = $res->get();
        if ($res){
            $res = $res->toArray();
        }
        return $res;
    }

    //删除活动
    static public function deleteActivity($id){
        return self::where('id',$id)->update(['status'=>2]);
    }

}
