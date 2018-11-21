<?php

namespace App\Http\Controllers\Admin;

use App\Models\WeChatAchieve;
use App\Transformers\WeChatAchieveTransformer;

class WeChatAchieveController extends AdminController
{
    function index()
    {
        $where['type'] = $this->request->get('type');
        $where['default'] = $this->request->get('default');
        $where['date'] = $this->request->get('date');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $WeChatAchieves = WeChatAchieve::where(function ($query) use ($where) {
            if ($where['date']) {
                $query->where('date', 'like', '%' . $where['date'] . '%');
            }
            if ($where['default'] != '') {
                $query->where('default', $where['default']);
            }
            if ($where['type']) {
                $query->where('type', $where['type']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($WeChatAchieves, new WeChatAchieveTransformer());
    }

    function get($id)
    {
        $WeChatAchieve = WeChatAchieve::find($id);
        if (!$WeChatAchieve) {
            return $this->errorNotFound();
        }
        return $this->item($WeChatAchieve, new WeChatAchieveTransformer());
    }

    function form($id = 0)
    {
        $validator = \Validator::make($this->request->input(), [
            'date' => 'required|max:20',
            'font' => 'required',
            'image' => 'required',
            'content' => 'required',
            'wechat_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $data = $this->request->input();
        if ($data['default'] == 1) {
            WeChatAchieve::where(['default' => 1, 'type' => $data['type']])->update(['default' => 0]);
        }
        WeChatAchieve::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function delete($id)
    {
        $WeChatAchieve = WeChatAchieve::find($id);
        if (!$WeChatAchieve) {
            return $this->errorNotFound();
        }
        $WeChatAchieve->delete();
        return $this->noContent();
    }

    function fonts()
    {
        return WeChatAchieve::pluck('font')->unique()->values()->all();
    }
}
