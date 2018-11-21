<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserSign;
use App\Transformers\AchievementTransformer;
use Illuminate\Support\Facades\Auth;

class AchievementController extends Controller
{
    function index()
    {
        $date = $_REQUEST['date'];
        $self = Achievement::where('date', '>=', date('Y-m', strtotime($date)))->where('date', '<', date('Y-m', strtotime('+1month', strtotime($date))))->where(['appid' => Auth::user()->cur_appid])->with('tips')->get()->toArray();
        $chenxi = Achievement::where('date', '>=', date('Y-m', strtotime($date)))->where('date', '<', date('Y-m', strtotime('+1month', strtotime($date))))->where(['appid' => 'wxa7852bf49dcb27d7'])->with('tips')->get()->toArray();
        return ['self' => $self, 'chenxi' => $chenxi];
    }

    function listing()
    {
        $user = $this->user()->switchToMiniUser();
        $UserSign = UserSign::where('appid', $user->last_appid)->where("user_id", $user->id)->select("date")->orderBy('id', 'DESC')->paginate(10);
        $date = [];
        foreach ($UserSign as $obj) {
            array_push($date, $obj->date);
        }
        $Achievement = Achievement::where("appid", $user->last_appid)
            ->whereIn("date", $date)
            ->select("id", "date", "image", "width", "height", "content")
            ->orderBy("date", 'DESC')
            ->get()->toArray();
        return $Achievement;
    }

    function get($id)
    {
        $achievement = Achievement::where('appid', '=', Auth::user()->cur_appid)->find($id);
        if (!$achievement) {
            return $this->errorNotFound();
        }
        return $this->item($achievement, new AchievementTransformer());
    }

    function form($id = 0)
    {
        $validator = \Validator::make($this->request->input(), [
            'date' => 'required|max:20',
            'image' => 'required',
            'content' => 'required',
            'tips' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $data = $this->request->input();
        $data['font'] = 'http://img.modubus.com/achievement/2017/10/23/苹方黑体-准-简.ttf';
        if ($data['default'] == 1) {
            Achievement::where(['default' => 1, 'type' => $data['type']])->update(['default' => 0]);
        }
        $data['appid'] = Auth::user()->cur_appid;
        unset($data['api_token']);
        unset($data['isadmin']);
        return Achievement::updateOrCreate(['id' => $id], $data)->id;
    }

    function delete($id)
    {
        $achievement = Achievement::where('appid', Auth::user()->cur_appid)->find($id);
        if (!$achievement) {
            return $this->errorNotFound();
        }
        $achievement->delete();
        return $this->noContent();
    }

    function fonts()
    {
        return Achievement::pluck('font')->unique()->values()->all();
    }

    function getimages()
    {
        $tips = $this->request->get('tips');
        return $this->paginator(Achievement::select('id', 'image', 'date', 'tips')->where(function ($query) use ($tips) {
            if ($this->request->has('tips')) {
                if (strstr($this->request->get('tips'), ',')) {
                    $query->whereIn('tips', explode(',', $tips));
                } else {
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
