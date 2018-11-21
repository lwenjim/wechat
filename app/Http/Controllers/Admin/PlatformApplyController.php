<?php

namespace App\Http\Controllers\Admin;
use App\Models\PlatformApply;
use App\Transformers\PlatFormApplyTransformer;
use App\Models\Wechat;
use App\Models\WechatUser;

class PlatformApplyController extends AdminController
{
    function index()
    {
        $list = PlatformApply::withCount('wechatUserSubscribeLogs')->orderBy('id','desc')->paginate();
        $pageInfo = $this->paginator($list, new PlatFormApplyTransformer());
        foreach ($pageInfo['data'] as $key => $value) {
            $pageInfo['data'][$key]['fans_count'] = WechatUser::where('wechat_id',$value['Wechat']['data']['id'])->count();
        }
        return $pageInfo;
    }

    function get($id)
    {
        $apply = PlatformApply::withCount('wechatUserSubscribeLogs')->find($id);
        if(empty($apply)){
            return [];
        }
        return $this->item($apply, new PlatFormApplyTransformer());
    }

    function del($id)
    {
        //PlatformApply::where(['id'=>$id])->delete();
        PlatformApply::where(['id'=>$id])->update(['delete'=>1]);
        $this->noContent();
    }

    function put($id, $status){
        PlatformApply::where('id', $id)->update(['status'=> $status]);
        $this->noContent();
    }
}
