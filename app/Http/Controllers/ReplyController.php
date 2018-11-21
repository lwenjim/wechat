<?php

namespace App\Http\Controllers;

use App\Transformers\ReplyTransformer;
use App\Models\Reply;
use App\Models\Wechat;

class ReplyController extends Controller
{
    function index()
    {
        $wechat_id = $this->cur_wechatid();
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $replies = Reply::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->where('wechat_id', $wechat_id)->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($replies, new ReplyTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $data['wechat_id'] = $this->cur_wechatid();
        unset($data['api_token']);
        Reply::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $reply = Reply::find($id);
        if (!$reply) {
            return $this->errorNotFound();
        }
        return $this->item($reply, new ReplyTransformer());
    }

    function delete($id)
    {
        $reply = Reply::find($id);
        if (!$reply) {
            return $this->errorNotFound();
        }
        $reply->delete();
        return $this->noContent();
    }

    private function cur_wechatid(){
        $appid = \Auth::user()->cur_appid;
        $wechat = Wechat::where('appid', $appid)->first();
        return $wechat->id;
    }
}