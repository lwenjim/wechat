<?php

namespace App\Http\Controllers\Admin;

use App\Models\Buy;
use App\Transformers\BuyTransformer;

class BuyController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'updated_at,desc');
        $where['export'] = $this->request->get('export', 0);
        list($order_field, $order_type) = explode(',', $where['order']);
        $buys = Buy::select('id', 'name', 'summary', 'number', 'start_time', 'end_time', 'market_price', 'express_price', 'coin', 'limit', 'status', 'sort', 'created_at', 'updated_at', \DB::raw("(select SUM(user_buy_order.buy_number) from user_buy_order where buy.id = user_buy_order.buy_id) as orders_count"))->where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type);
        if ($where['export']) {
            $list = $buys->get();
            $data = [];
            $status = ['已下架', '已上架'];
            foreach ($list as $k => $v) {
                $data[$k]['编号'] = $v->id;
                $data[$k]['名称'] = $v->name;
                $data[$k]['描述'] = $v->summary;
                $data[$k]['规则'] = $v->rule;
                $data[$k]['目标人数'] = $v->number;
                $data[$k]['参与人数'] = $v->orders_count;
                $data[$k]['开始时间'] = $v->start_time;
                $data[$k]['结束时间'] = $v->end_time;
                $data[$k]['所需魔币'] = $v->coin;
                $data[$k]['每人限购'] = $v->limit;
                $data[$k]['市场价'] = $v->market_price;
                $data[$k]['快递费'] = $v->express_price;
                $data[$k]['状态'] = $status[$v->status];
            }
            return app('excel')->create(date('Y-m-d H:i:s') . '魔都巴士魔币夺宝商品列表', function ($excel) use ($data) {
                $excel->sheet('魔都巴士魔币夺宝商品列表', function ($sheet) use ($data) {
                    $sheet->fromArray($data, 'null', 'A1', true, true);
                });
            })->export('xlsx');
        } else {
            $list = $buys->paginate();
        }
        return $this->paginator($list, new BuyTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:50',
            'images' => 'required',
            'number' => 'required',
            'summary' => 'required',
            'start_time' => 'required',
            'end_time' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $buy = Buy::updateOrCreate(['id' => $id], array_except($data, ['content']));
        if ($buy) {
            if ($id) {
                $buy->content()->update(['content' => $data['content']]);
            } else {
                $buy->content()->create(['content' => $data['content']]);
            }
        }
        return $buy->id;
    }

    function get($id)
    {
        $buy = Buy::findOrFail($id);
        return $this->item($buy, new BuyTransformer());
    }

    function delete($id)
    {
        $buy = Buy::findOrFail($id);
        $buy->delete();
        return $this->noContent();
    }
}
