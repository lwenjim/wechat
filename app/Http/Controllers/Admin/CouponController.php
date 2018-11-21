<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coupon;
use App\Models\CouponItem;
use App\Transformers\CouponTransformer;

class CouponController extends AdminController
{
    function index()
    {
        $where['begin_time'] = $this->request->get('begin_time');
        $where['end_time'] = $this->request->get('end_time');
        $where['status'] = $this->request->get('status');
        $where['name'] = $this->request->get('name');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $list = Coupon::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            if ($where['begin_time']) {
                $query->where('begin_time', '>=', $where['begin_time']);
            }
            if ($where['end_time']) {
                $query->where('end_time', '<=', $where['end_time']);
            }
        })->withCount(['items', 'items AS items0' => function ($query) {
            $query->where('status', 0);
        }, 'items AS items1' => function ($query) {
            $query->where('status', 1);
        }, 'items AS items2' => function ($query) {
            $query->where('status', 2);
        }, 'items AS items3' => function ($query) {
            $query->where('status', 3);
        }])->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($list, new CouponTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:255',
            'begin_time' => 'required',
            'end_time' => 'required',
            'money' => 'required',
            'price' => 'required'
        ], ['name.required' => '名称不能为空!']);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $coupon = Coupon::updateOrCreate(['id' => $id], array_except($data, ['products']));
        if (isset($data['products']) && !empty($data['products'])) {
            $coupon->products()->sync(explode(',', $data['products']));
        }
        return $this->created();
    }

    function make($id)
    {
        $number = $this->request->input('number');
        for ($index = 0; $index < $number; $index++) {
            $data['coupon_id'] = $id;
            CouponItem::create($data);
        }
        return $this->created();
    }

    function send($id)
    {
        $user_ids = $this->request->input('user_ids');
        if (!$user_ids) {
            return $this->errorBadRequest('用户不能为空');
        }
        $user_array = explode(',', $user_ids);
        $coupon = Coupon::where(['id' => $id, 'status' => 1])
            ->where('end_time', '>=', date('Y-m-d H:i:s'))
            ->whereHas('items', function ($query) {
                $query->where('status', 0);
            })->firstOrFail();
        if (count($user_array) > $coupon->items()->where('status', 0)->count()) {
            return $this->errorBadRequest('优惠券不够了！');
        }
        foreach ($user_array as $user_id) {
            $coupon_item = $coupon->items()->where('status', 0)->first();
            if ($coupon_item) {
                $coupon_item->update(['user_id' => $user_id, 'got_at' => date('Y-m-d H:i:s'), 'status' => 1]);
            }
        }
        return $this->created();
    }

    function get($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return $this->errorNotFound();
        }
        return $this->item($coupon, new CouponTransformer());
    }

    function delete($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return $this->errorNotFound();
        }
        $coupon->delete();
        return $this->noContent();
    }

    function deleteItem($item_id)
    {
        $couponItem = CouponItem::find($item_id);
        if (!$couponItem) {
            return $this->errorNotFound();
        }
        $couponItem->delete();
        return $this->noContent();
    }
}
