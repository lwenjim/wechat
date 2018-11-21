<?php

namespace App\Http\Controllers;

use App\Models\PlatformApply;
use App\Models\Wechat;

class PlatformApplyController extends Controller
{
    function post()
    {
        $id = $this->request->input('id');
        $data = [
            'user_id' => $this->user()->id,
            'appid' => $this->user()->cur_appid,
        ];
        if(PlatformApply::where($data)->first()){
            return $this->noContent();
        }
        PlatformApply::updateOrCreate(['id' => $id], $data);
        $this->noContent();
    }

    function del()
    {
        //PlatformApply::where(['user_id' => $this->user()->id, 'appid' => $this->user()->cur_appid])->delete();
        PlatformApply::where(['user_id' => $this->user()->id, 'appid' => $this->user()->cur_appid])->update(['delete'=>1]);
        $this->noContent();
    }

    function find(){
        $appid = $this->request->input('appid');
        $data = PlatformApply::where('appid', $appid)->first();
        if (is_null($data)) {
            return 0;
        }
        return $data->status;
    }

    function put($id,$status){
        PlatformApply::where(['id', $id])->update(['status'=> $status]);
        $this->noContent();
    }
}
