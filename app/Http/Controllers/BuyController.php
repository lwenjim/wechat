<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Models\UserBuyOrder;
use App\Models\UserAddress;
use App\Transformers\BuyTransformer;
use DB;

class BuyController extends Controller
{
    public function list()
    {
        $orders_count = \DB::raw("(select SUM(user_buy_order.buy_number) from user_buy_order where buy.id = user_buy_order.buy_id and user_buy_order.created_at>=buy.start_time and user_buy_order.created_at<=buy.end_time) as orders_count");
        $buys = Buy::select('id', 'name', 'images', 'number', 'start_time', 'end_time', 'coin','summary','market_price', $orders_count)
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->paginate();
        return $this->paginator($buys, new BuyTransformer());
    }

    public function detail($id)
    {
        $buy = Buy::where(['id' => $id, 'status' => 1])->firstOrFail();
        $buy->datetime = date('Y-m-d H:i:s');
        $buy->orders_count = $buy->orders()->whereBetween('created_at', [$buy->start_time, $buy->end_time])->sum('buy_number');
        return $this->item($buy, new BuyTransformer());
    }

    public function order($buy_id, $address_id)
    {
        $user_id = $this->user()->id;
        DB::beginTransaction();
        try {
            $buy = Buy::select('id', 'name', 'number', 'coin', 'limit', 'express_price','market_price')->where([['status', '=', 1], ['id', '=', $buy_id], ['start_time', '<=', date('Y-m-d H:i:s')], ['end_time', '>=', date('Y-m-d H:i:s')]])->sharedLock()->first();
            if ($buy) {
                $buy_number = $this->request->input('number', 1);
                $buy_coin = $buy_number * $buy->coin;

                $user_order_count = UserBuyOrder::where(['buy_id' => $buy_id, 'user_id' => $user_id])->where('status', 'confirm')->sum('buy_number');
                if ($buy->limit > 0 && ($user_order_count + $buy_number) > $buy->limit) {
                    throw new \Exception('每人只能购买' . $buy->limit . '次哦');
                }
                $data = [];
                $data['user_id'] = $user_id;
                $data['buy_id'] = $buy->id;
                $data['trade_no'] = date('YmdHis') . mt_rand(1000, 9999);
                $address = UserAddress::select('name', 'mobile', 'email', 'province', 'city', 'district', 'address')->where('id', $address_id)->where('user_id', $user_id)->first();
                $data['address_id'] = $address_id;
                $data['address_name'] = $address->name;
                $data['address_mobile'] = $address->mobile;
                $data['address_email'] = $address->email;
                $data['address_province'] = $address->province;
                $data['address_city'] = $address->city;
                $data['address_district'] = $address->district;
                $data['address_address'] = $address->address;
                $data['market_price'] = $buy->market_price;
                $data['express_price'] = $buy->express_price;
                $data['buy_name'] = $buy->name;
                $data['buy_number'] = $buy_number;
                $data['buy_coin'] = $buy_coin;
                $data['pay_type'] = 'mbrmb';
                $data['pay_status'] = 'unpaid';
                $data['remark'] = $this->request->input('remark');
                $order = UserBuyOrder::create($data);

                $data['first'] = '晨夕时刻提醒';
                $data['keyword1'] = $buy->name;
                $data['keyword2'] = $order->trade_no;
                $data['keyword3'] = '您参与的晨夕秒杀活动订单已经生成～';
                sendTplMsg('DqSN7SOkz-5JjiPHqdqsutKtTcMV9NTH2jLUnxWSF4E', config('app.url') . '/static/h5/mbbuy.html#' . $buy_id, $data, $this->user()->openid);
            } else {
                throw new \Exception('暂时不能购买');
            }
            DB::commit();
            return ['order_id' => $order->id];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorBadRequest($e->getMessage());
        }
    }

    public function remind($type = null)
    {
        $redis = app('redis');
        $key = 'buy:remind:' . $this->user()->id;
        if ($type == 'set') {
            return (int)$redis->set($key, $this->user()->openid);
        } elseif ($type == 'del') {
            return (int)$redis->del($key);
        } else {
            return (int)$redis->exists($key);
        }
    }
}