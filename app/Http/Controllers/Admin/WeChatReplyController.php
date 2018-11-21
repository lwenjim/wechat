<?php

namespace App\Http\Controllers\Admin;

use App\Transformers\WeChatReplyTransformer;
use App\Models\WeChatReply;

class WeChatReplyController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $replies = WeChatReply::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($replies, new WeChatReplyTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:50',
            'wechat_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        WeChatReply::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $reply = WeChatReply::find($id);
        if (!$reply) {
            return $this->errorNotFound();
        }
        return $this->item($reply, new WeChatReplyTransformer());
    }

    function delete($id)
    {
        $reply = WeChatReply::find($id);
        if (!$reply) {
            return $this->errorNotFound();
        }
        $reply->delete();
        return $this->noContent();
    }
}