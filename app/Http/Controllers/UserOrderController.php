<?php

namespace App\Http\Controllers;

use App\Models\ProductComment;
use App\Models\UserOrder;
use App\Models\UserOrderProduct;
use App\Models\UserOrderComment;
use App\Models\UserOrderAfter;
use App\Transformers\UserOrderTransformer;

class UserOrderController extends Controller
{
    public function list()
    {
        $userOrders = UserOrder::select('id', 'trade_no', 'status', 'pay_type', 'pay_status', 'product_price', 'product_number', 'express_price', 'express_name', 'content')->where('user_id', $this->user()->id)->orderBy('id', 'desc')->get();
        return $this->collection($userOrders, new UserOrderTransformer());
    }

    public function get($id)
    {
        $userOrder = UserOrder::where(['id' => $id, 'user_id' => $this->user()->id])->first();
        if (!$userOrder) {
            return $this->errorNotFound();
        }
        return $this->item($userOrder, new UserOrderTransformer());
    }

    public function put($id)
    {
        $status = $this->request->input('status');
        if ($status == 'canceled') {
            $response = cancelOrder($id);
        } else {
            $response = UserOrder::where('id', $id)->update(['status' => $status]);
        }
        if ($status == 'canceled') {
            $action = 'cancel';
            $remark = '取消订单';
        } elseif ($status == 'finished') {
            $score1 = $this->request->input('score1', 5);
            $score2 = $this->request->input('score2', 5);
            $score3 = $this->request->input('score3', 5);
            UserOrderComment::create(['user_id' => $this->user()->id, 'user_order_id' => $id, 'score1' => $score1, 'score2' => $score2, 'score3' => $score3]);
            $action = 'finish';
            $remark = '订单完成';
        } elseif ($status == 'commenting') {
            $action = 'comment';
            $remark = '确认收货';
        } elseif ($status == 'aftersale') {
            $product_id = $this->request->input('product_id');
            $product_data = $this->request->input('product_data');
            UserOrderAfter::updateOrCreate(['user_id' => $this->user()->id, 'user_order_id' => $id, 'user_order_product_id' => $product_id], $product_data);
            $status = [
                1 => '申请中',
                2 => '同意申请',
                3 => '不同意申请',
                4 => '已退款或退货',
            ];
            $action = 'aftersale';
            $remark = $status[$product_data['status']] . '；' . '理由：' . $product_data['reason'];
        } else {
            $action = '';
            $remark = '';
        }
        if ($action && $remark) {
            sendOrderLog($this->user()->id, $id, 'user', $action, $remark);
        }
        return (int)$response;
    }

    public function post($id)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'score' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        if (ProductComment::where('user_order_product_id', $id)->count()) {
            return $this->errorBadRequest('您已评论过了');
        }
        $product = UserOrderProduct::select('id', 'user_order_id', 'product_id', 'product_spec_id')->findOrFail($id);
        $data['product_id'] = $product->product_id;
        $data['product_spec_id'] = $product->product_spec_id;
        $data['user_id'] = $this->user()->id;
        $data['user_order_id'] = $product->user_order_id;
        $data['user_order_product_id'] = $product->id;
        ProductComment::create($data);
        return $this->created();
    }

    public function delete($id)
    {
        $userOrder = UserOrder::where(['id' => $id, 'user_id' => $this->user()->id])->first();
        if (!$userOrder) {
            return $this->errorNotFound();
        }
        $userOrder->delete();
        return $this->noContent();
    }
}