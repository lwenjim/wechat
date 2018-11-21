<?php

namespace App\Http\Controllers\Admin;

use App\Models\Achievement;
use App\Transformers\AchievementTransformer;
use Illuminate\Support\Facades\Auth;

class AchievementController extends AdminController
{

    const official_account_appid = 'wxa7852bf49dcb27d7';

    function index(){
        // $where['type'] = $this->request->get('type');
        // $where['default'] = $this->request->get('default');
        // $where['date'] = $this->request->get('date');
        // $where['order'] = $this->request->get('order','created_at,desc');
        // list($order_field,$order_type) = explode(',', $where['order']);
        // $achievements = Achievement::where(function($query) use ($where) {
        //     if($where['date']){
        //         $query->where('date','like','%'.$where['date'].'%');
        //     }
        //     if($where['default']!=''){
        //         $query->where('default',$where['default']);
        //     }
        //     if($where['type']){
        //         $query->where('type',$where['type']);
        //     }
        // })->orderBy($order_field,$order_type)->paginate();
        // return $this->paginator($achievements, new AchievementTransformer());
        $date = $_REQUEST['date'];
        return Achievement::where('date','>=',date('Y-m',strtotime($date)))-> where('date','<',date('Y-m',strtotime('+1month',strtotime($date))))->where(['appid'=>'wxa7852bf49dcb27d7'])->with('tips')->get();
    }

    function get($id){
        $achievement = Achievement::find($id);
        if (! $achievement) {
            return $this->errorNotFound();
        }
        return $this->item($achievement, new AchievementTransformer());
    }

    function form($id=0){
        $validator = \Validator::make($this->request->input(), [
            'date' => 'required|max:20',
            'font' => 'required',
            'image' => 'required',
            'content' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $data = $this->request->input();
        if($data['default']==1){
            Achievement::where(['default'=>1,'type'=>$data['type']])->update(['default'=>0]);
        }
        unset($data['isadmin']);
        $data['appid'] = self::official_account_appid;
        Achievement::updateOrCreate(['id'=>$id],$data);
        return $this->created();
    }

    function delete($id){
        $achievement = Achievement::find($id);
        if (! $achievement) {
            return $this->errorNotFound();
        }
        $achievement->delete();
        return $this->noContent();
    }

    function fonts(){
        return Achievement::pluck('font')->unique()->values()->all();
    }

    function getimages()
    {
        $tips = $this->request->get('tips');
        return $this->paginator(Achievement::select('id','image','date','tips')->where(function ($query) use ($tips) {
            if ($this->request->has('tips')) {
                if (strstr($this->request->get('tips'), ',')) {
                    $query->whereIn('tips', explode(',', $tips));
                }else{
                    $query->where('tips', $tips);
                }
            }
        })->orderby('created_at', 'desc')->paginate(30), new AchievementTransformer());
    }

    function syncCardConfig($id, $appids, $date = '')
    {
        if (!empty($date) && !preg_match('/\d{4}\-\d{1,2}\-\d{1,2}/', $date)) {
            info($this->request->fullUrl());
            return $this->errorBadRequest('日期格式有误' . $date);
        }
        $id = explode(',', $id);
        $appids = explode(',', $appids);
        $achis = Achievement::whereIn('id', $id)->get();
        if (empty($achis)) {
            return $this->errorBadRequest('empty card');
        }
        foreach ($achis as $k => $card) {
            if (!empty($date)) {
                $card->date = $date;
            }
            foreach ($appids as $appid) {
                $card->appid = $appid;
                $card2 = Achievement::where(['appid' => $appid, 'date' => $card->date])->first();
                if (!empty($card2)) {
                    $card2->update($card->toArray());
                } else {
                    $card->id = '';
                    Achievement::create($card->toArray());
                }
            }
        }
        return $this->created();
    }
}
