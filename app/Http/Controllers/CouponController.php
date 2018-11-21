<?php

namespace App\Http\Controllers;

use App\Models\Coupon;

class CouponController extends Controller
{
    public function list()
    {
		$user = $this->user();
        return Coupon::select('id', 'name', 'image', 'money', 'price', 'begin_time', 'end_time', 'content')->where('status', 1)->withCount(['items AS items1' => function ($query) {
            $query->where('status', 1)->where('user_id', $user->id);
        }, 'items AS items2' => function ($query) {
            $query->where('status', 2)->where('user_id', $user->id);
        }, 'items AS items3' => function ($query) {
            $query->where('status', 3)->where('user_id', $user->id);
        }])->whereHas('items', function ($query) {
            $query->where('user_id', $user->id);
        })->with(['items' => function ($query) {
            $query->select('id', 'coupon_id', 'content', 'got_at', 'used_at', 'status')->where('user_id', $user->id);
        }])->orderBy('sort', 'asc')->orderBy('id', 'desc')->paginate();
    }

    public function get($id)
    {
        $coupon = Coupon::where(['id' => $id, 'status' => 1])
            ->where('end_time', '>=', date('Y-m-d H:i:s'))
            ->whereHas('items', function ($query) {
                $query->where('status', 0);
            })->firstOrFail();
        $coupon_item = $coupon->items()->where('status', 0)->first();
        if ($coupon_item) {
            $coupon_item->update(['user_id' => $this->user()->id, 'got_at' => date('Y-m-d H:i:s'), 'status' => 1]);
        }
        return $this->created();
    }
}