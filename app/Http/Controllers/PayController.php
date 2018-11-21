<?php

namespace App\Http\Controllers;

use App\Models\UserOrder;
use App\Models\UserSignAdd;
use App\Models\UserBuyOrder;

class PayController extends Controller
{
    public function pay($order_id, $type)
    {
        switch ($type) {
            case 'shop':
                $order = UserOrder::select('express_price', 'product_price', 'trade_no')->where('id', $order_id)->orderBy('id', 'desc')->first();
                $data['out_trade_no'] = "$order->trade_no";
                $data['total_fee'] = ($order->express_price + $order->product_price) * 100;
                break;
            case 'sign':
                $order = UserSignAdd::select('id', 'money', 'trade_no')->where('id', $order_id)->orderBy('id', 'desc')->first();
                $data['out_trade_no'] = "$order->trade_no";
                $data['total_fee'] = $order->money * 100;
                break;
            case 'seckill':
                $order = UserBuyOrder::select('market_price','express_price','trade_no','buy_number')->where('id',$order_id)->orderBy('id','desc')->first();
                $data['out_trade_no'] = "$order->trade_no";
                $price = $order->market_price;
                if($this->user()->group_id>0){
                    $price = $order->buy_coin;
                }
                $data['total_fee'] = ($order->express_price + $price * $order->buy_number) * 100;
                break;
            default:
                $order = null;
        }
        if ($order) {
            $payment = app('wechat')->payment;
            $data['trade_type'] = "JSAPI";
            $data['body'] = "晨夕时刻产品及服务消费";
            $data['notify_url'] = config('app.url') . "/api/wechat/notify";
            $data['openid'] = $this->user()->openid;
            $data['attach'] = $type . ':' . $this->user()->id;
            $pay_order = new \EasyWeChat\Payment\Order($data);
            $result = $payment->prepare($pay_order);
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
                app('redis')->setex('mornight:active:miniprogram:tplmsg:' . $this->user()->openid . ':' . $result->prepay_id, 604800, 3);
                return $payment->configForPayment($result->prepay_id);
            } else {
                return $this->errorBadRequest($result->err_code . ',' . $result->err_code_des);
            }
        } else {
            return $this->errorNotFound();
        }
    }

    public function notify()
    {
        $wechat = app('wechat');
        $response = $wechat->payment->handleNotify(function ($notify, $successful) use ($wechat) {
            if ($successful) {
                list($type, $user_id) = explode(':', $notify->attach);
                switch ($type) {
                    case 'shop':
                        $order = UserOrder::where('trade_no', $notify->out_trade_no)->first();
                        if ($order->pay_status == 'paid') {
                            return true;
                        }
                        sendOrderLog(0, $order->id, 'wechat', 'pay', '付款成功');
                        sendTplMsg('psmKVj6CaoPRTt8eShMgP4WUf7O-bbzm3L1NNZkfDZM', 'pages/orders/orders', ['keyword1' => $order->trade_no, 'keyword2' => $order->product_price + $order->express_price, 'keyword3' => $order->products()->value('title')], $notify->openid);
                        return $order->update(['pay_status' => 'paid', 'status' => 'delivering']);
                    case 'sign':
                        $order = UserSignAdd::where('trade_no', $notify->out_trade_no)->first();
                        if ($order->status) {
                            return true;
                        }
                        //发送到消息队列
                        dispatch((new \App\Jobs\SignAdd($order->user, $order->user_ip, $order->date))->onQueue('sign'));
                        //消息提醒
                        sendMsg($user_id, '补签成功提醒', 'sign', '恭喜您' . $order->date . '补签成功！');
                        return $order->update(['status' => 1]);
                    case 'seckill':
                        $order = UserBuyOrder::where('trade_no',$notify->out_trade_no)->first();
                        if ($order->pay_status == 'paid') {
                            return true;
                        }
                        sendOrderLog(0, $order->id, 'wechat', 'pay', '付款成功');
                        $data = [
                            'keyword1' => $order->trade_no,
                            'keyword2' => $order->product_price + $order->express_price,
                            'keyword3' => $order->buy()->value('name')
                        ];
                        sendTplMsg('psmKVj6CaoPRTt8eShMgP4WUf7O-bbzm3L1NNZkfDZM', 'pages/orders/orders', $data, $notify->openid);
                        return $order->update(['pay_status' => 'paid', 'status' => 'delivering']);
                        break;
                    default:
                        return 'Order not exist.';
                }
            } else {
                return false;
            }
        });
        return $response;
    }
}