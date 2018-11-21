<?php

namespace App\Http\Controllers\Admin;

use App\Models\UserOrder;
use App\Models\UserOrderProduct;
use App\Models\UserOrderAfter;
use App\Transformers\UserOrderTransformer;

class UserOrderController extends AdminController
{
    function index()
    {
        $where['status'] = $this->request->get('status');
        $where['search'] = $this->request->get('search');
        $where['keyword'] = $this->request->get('keyword');
        $where['pay_type'] = $this->request->get('pay_type');
        $where['pay_status'] = $this->request->get('pay_status');
        $where['export'] = $this->request->get('export', 0);
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $userOrder = UserOrder::where(function ($query) use ($where) {
            if ($where['keyword']) {
                if ($where['search'] == 'order') {
                    $query->where('trade_no', 'like', '%' . $where['keyword'] . '%');
                        // ->orWhere('express_name', 'like', '%' . $where['keyword'] . '%')
                        // ->orWhere('address_email', 'like', '%' . $where['keyword'] . '%')
                        // ->orWhere('address_mobile', 'like', '%' . $where['keyword'] . '%');
                } else {
                    $user_order_id = UserOrderProduct::where('title', 'like', '%' . $where['keyword'] . '%')->pluck('user_order_id')->toArray();
                    if (empty($user_order_id)) {
                        $query->where('id', 0);
                    }else{
                        $query->whereIn('id', $user_order_id);
                    }
                }
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            if ($where['pay_type'] != '') {
                $query->where('pay_type', $where['pay_type']);
            }
            if ($where['pay_status'] != '') {
                $query->where('pay_status', $where['pay_status']);
            }
        })->orderBy($order_field, $order_type);
        if ($where['export']) {
            $list = $userOrder->get();
            $data = [];
            $pay_type = [
                'rmb' => '现金支付'
            ];
            $pay_status = [
                'paid' => '已支付',
                'unpaid' => '未支付',
                'refunded' => '已退款'
            ];
            foreach ($list as $k => $v) {
                foreach ($v->products as $product) {
                    $data[$k]['支付方式'] = isset($pay_type[$v->pay_type]) ? $pay_type[$v->pay_type] : $v->pay_type;
                    $data[$k]['支付状态'] = isset($pay_status[$v->pay_status]) ? $pay_status[$v->pay_status] : $v->pay_status;
                    $data[$k]['收件人姓名'] = $v->address_name;
                    $data[$k]['收件人地址'] = $v->address_province . $v->address_city . $v->address_district . $v->address_address;
                    $data[$k]['收件人手机'] = $v->address_mobile;
                    $data[$k]['物品名称'] = ($product->product ? $product->product->short_title : $product->title) . ':' . $product->spec;
                    $data[$k]['物品数量'] = $v->number;
                    $data[$k]['订单ID'] = $v->id;
                    $data[$k]['订单编号'] = $v->trade_no;
                    $data[$k]['买家ID'] = $v->user_id;
                    $data[$k]['买家留言'] = $v->remark;
                    $data[$k]['代收货款'] = $v->product_price + $v->express_price;
                    $data[$k]['备注'] = '请开箱检查再签收';
                }
            }
            app('excel')->create(date('Y-m-d H:i:s') . '蜜拉蜜拉订单', function ($excel) use ($data) {
                $excel->sheet('蜜拉蜜拉订单', function ($sheet) use ($data) {
                    $sheet->fromArray($data, 'null', 'A1', true, true);
                });
            })->export('xlsx');
        } else {
            $list = $userOrder->paginate();
        }
        return $this->paginator($list, new UserOrderTransformer());
    }

    function get($id)
    {
        $userOrder = UserOrder::where('id', $id)->first();
        if (!$userOrder) {
            return $this->errorNotFound();
        }
        return $this->item($userOrder, new UserOrderTransformer());
    }

    function delete($id)
    {
        $userOrder = UserOrder::find($id);
        if (!$userOrder) {
            return $this->errorNotFound();
        }
        $userOrder->delete();
        return $this->noContent();
    }

    function cancel($id)
    {
        cancelOrder($id);
        sendOrderLog($this->user()->id, $id, 'admin', 'cancel', '取消订单');
        return $this->created();
    }

    function send($id)
    {
        $content = $this->request->input('content');
        $userOrder = UserOrder::find($id);
        if (!$userOrder) {
            return $this->errorNotFound();
        }
        $result = $userOrder->update(['status' => 'delivered', 'content' => $content]);
        if ($result) {
            sendOrderLog($this->user()->id, $id, 'admin', 'express', '订单发货');
            sendTplMsg('ihEblyOyKdTVBFvrB7GIWig0HNcemBBwgy3b8QkPnf0', 'pages/orders/orders', ['keyword1' => $userOrder->trade_no, 'keyword2' => $userOrder->content, 'keyword3' => $userOrder->product_price + $userOrder->express_price, 'keyword4' => $userOrder->products()->value('title') . '等'], $userOrder->user->openid);
        }
        return $this->created();
    }

    function after($id)
    {
        $status = $this->request->input('status');
        $reply = $this->request->input('reply');
        $after = UserOrderAfter::find($id);
        if (!$after) {
            return $this->errorNotFound();
        }
        $result = $after->update(['status' => $status, 'reply' => $reply]);
        if ($result) {
            $status_arr = [
                1 => '申请中',
                2 => '同意申请',
                3 => '不同意申请',
                4 => '已退款或退货',
            ];
            $remark = $status_arr[$status] . '；' . '回复：' . $reply;
            sendOrderLog($this->user()->id, $id, 'admin', 'aftersale', $remark);
        }
        return $this->created();
    }

    function price($id)
    {
        $type = $this->request->input('type');
        $price = $this->request->input('price');
        $userOrder = UserOrder::where('id', $id)->where('pay_status', 'unpaid')->first();
        if (!$userOrder) {
            return $this->errorNotFound();
        }
        if ($type == 'express') {
            if ($price > 0) {
                $result = $userOrder->increment('express_price', $price);
            } else {
                $result = $userOrder->decrement('express_price', abs($price));
            }
            $type_name = '快递';
            $type_price = $userOrder->express_price;
        } else {
            if ($price > 0) {
                $result = $userOrder->increment('product_price', $price);
            } else {
                $result = $userOrder->decrement('product_price', abs($price));
            }
            $type_name = '产品';
            $type_price = $userOrder->product_price;
        }
        if ($result) {
            sendOrderLog($this->user()->id, $id, 'admin', 'price', '订单' . $type_name . '改价,原价:' . $type_price . ',更改金额:' . $price);
            sendStaffMsg('text', '订单号:' . $userOrder->trade_no . '已经改价成功！请及时付款', $userOrder->user->openid);
        }
        return $this->created();
    }

    function refund($trade_no)
    {
        $fee = $this->request->input('fee', 0);
        $result = refundOrder($trade_no, $fee);
        if (!$result) {
            return $this->noContent();
        }
        return $this->created();
    }

    function query($no, $type)
    {
        $payment = app('wechat')->payment;
        if ($type == 1) {
            $result = $payment->query($no);
        } elseif ($type == 2) {
            $result = $payment->queryRefund($no);
        } else {
            $result = $payment->queryByTransactionId($no);
        }
        return $result;
    }
}
