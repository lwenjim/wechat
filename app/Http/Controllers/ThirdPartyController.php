<?php
/*
|--------------------------------------------------------------------------
| 三方
|--------------------------------------------------------------------------
| author: ygq
| time: 20180814
| desc: 广告合作
|
*/
namespace App\Http\Controllers;

use App\Models\Wechat;
use App\Models\WXMaterialNewsSendedHistory;

class ThirdPartyController extends Controller{

    public function read()
    {
        $hour = date('H');
        $redis = app('redis');
        $clock = config('config.TaskIntervalPointer');
        $preMaxTimes = config('config.PreMaxReadArticleTimes');
        $nextMaxTimes = config('config.NextMaxReadArticleTimes');
        $params = $this->request->only(['uid', 'score', 'appid']);
        $key = "mornight:account:todayRead:{$params['uid']}:" . ($hour < $clock ? 'pre' : 'nex');

        $validator = \Validator::make($params, [
            'uid' => 'required|string|max:10',
            'score' => 'required|int|max:100']
        );
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $readedTimes = $redis->get($key);
        if ($hour < $clock && $readedTimes >= $preMaxTimes || $hour >= $clock && $readedTimes >= $nextMaxTimes) {
            return $this->errorBadRequest('read times out');
        }
        $wechatId = !empty($params['appid']) ? Wechat::where(['appid' => $params['appid']])->value('id') : 0;
        fisherMissionAward($wechatId, $params['uid'], 'read');
        changeCoin($params['uid'], $params['score'], 'read', 0, '阅读送原力');

        $redis->incr($key);
        $redis->expire($key, untilTomorrow());
        return $this->created();
    }

    //获取公众号信息
    public function getGZHInfos(\Illuminate\Http\Request $request){
        $method = $request->get('m','default');
        switch ($method) {
            case 'gzh_infos':
                $this->gzh_infos();
                break;
            default:
                $json = [
                    'status'=>200,
                    'result'=>[],
                    'message'=>'success',
                ];
                echo json_encode($json);
                break;
        }
    }

    //获取公众号信息
    private function gzh_infos(){
        $res = Wechat::get()->toArray();
        $res1 = [];
        array_walk($res,function($v)use(&$res1){
            $res1[$v['id']] = [
                'id'=>$v['id'],
                'name'=>$v['name'],
                'bd_code'=>$v['bd_code'],
            ];
        });
        if($res1){
            $json = [
                'status'=>200,
                'result'=>$res1,
                'message'=>'获取公众号信息成功',
            ];
            echo json_encode($json);
        }else{
            $json = [
                'status'=>230,
                'result'=>[],
                'message'=>'获取公众号信息失败',
            ];
            echo json_encode($json);
        }
    }

    //获取公众号在晨夕后台推送的文章记录
    public function getArticle()
    {
        $appid = $this->request->get('appid',false);
        if($appid){
            $tow_days_ago = date('Y-m-d H:i:s',strtotime(date('Y-m-d')) - 3600 * 24 * 2);
            $list = WXMaterialNewsSendedHistory::where('wechat_id',$appid)
                ->where('created_at','>=',$tow_days_ago)
                ->orderBy('created_at','desc')
                ->get()
                ->toArray();
            $json = [
                'status'=>200,
                'result'=>$list,
                'message'=>'获取公众号文章成功',
            ];
            echo json_encode($json);
        }else{
            $json = [
                'status'=>230,
                'result'=>[],
                'message'=>'缺少参数:appid',
            ];
            echo json_encode($json);
        }
    }

    public function getActiveNum(){
        $ids = $this->request->get('ids',false);
        if($ids){
            $ids = explode('|',$ids);
            $list = Wechat::whereIn('id',$ids)->select(['id','appid','name'])->get()->toArray();
            array_walk($list,function(&$v){
                $res = getGzhStatic($v['appid']);
                $res1 = $res['todayWechatStats'];
                if($res1 && isset($res1['active'])){
                    $v['active'] = $res1['active'];
                }else{
                    $v['active'] = 0;
                }
            });
            $json = [
                'status'=>200,
                'result'=>$list,
                'message'=>'获取活跃数成功',
            ];
            echo json_encode($json);
        }else{
            $json = [
                'status'=>230,
                'result'=>[],
                'message'=>'缺少参数:ids',
            ];
            echo json_encode($json);
        }
    }
}