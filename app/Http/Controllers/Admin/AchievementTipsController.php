<?php

namespace App\Http\Controllers\Admin;

use App\Models\AchievementTips;

class AchievementTipsController extends AdminController
{
    public function detail($id){
        return AchievementTips::where('id', $id)->first();
    }

    public function list(){
        return AchievementTips::select('id', 'name', 'sort')->where('is_del', 0)->orderBy('sort','asc')->get();
    }

    public function add(){
        return is_null(AchievementTips::where('name', $this->request->get('name'))->first()) ? (AchievementTips::create(['name'=>$this->request->get('name')])? $this->created() : $this->error('添加失败', 400)) : $this->error('名称已存在', 400);
    }

    public function change($id){
        AchievementTips::where('id', $id)->update(['name'=>$this->request->get('name'), 'sort'=>$this->request->get('sort')]);
        return $this->created();
    }

    public function delete($id){
        AchievementTips::where('id', $id)->update(['is_del'=>1]);
        return $this->noContent();
    }
}
