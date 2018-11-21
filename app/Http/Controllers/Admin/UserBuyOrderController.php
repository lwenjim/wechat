<?php

namespace App\Http\Controllers\Admin;

use App\Models\UserBuyOrder;
use App\Transformers\UserBuyOrderTransformer;
use Illuminate\Support\Facades\DB;

class UserBuyOrderController extends AdminController
{
    function index()
    {
        $where['status'] = $this->request->get('status');
        $where['keyword'] = $this->request->get('keyword');
        $where['pay_type'] = $this->request->get('pay_type');
        $where['pay_status'] = $this->request->get('pay_status');
        $where['export'] = $this->request->get('export', 0);
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $buyOrder = UserBuyOrder::select(['user_buy_order.*',DB::raw('(select if(`user`.group_id IS NULL,user_buy_order.express_price+user_buy_order.market_price,user_buy_order.buy_coin+user_buy_order.express_price) from `user` where `user`.id=user_buy_order.user_id) as total_cost')])->where(function ($query) use ($where) {
            if ($where['keyword']) {
                $query
                    ->orWhere('trade_no', 'like', '%' . $where['keyword'] . '%')
                    ->orWhere('buy_name', 'like', '%' . $where['keyword'] . '%')
                    ->orWhere('address_name', 'like', '%' . $where['keyword'] . '%')
                    ->orWhere('address_email', 'like', '%' . $where['keyword'] . '%')
                    ->orWhere('address_mobile', 'like', '%' . $where['keyword'] . '%');
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
            $list = $buyOrder->get();
            $data = [];
            foreach ($list as $k => $v) {
                $data[$k]['收件人姓名'] = $v->address_name;
                $data[$k]['收件人地址'] = $v->address_province . $v->address_city . $v->address_district . $v->address_address;
                $data[$k]['收件人手机'] = $v->address_mobile;
                $data[$k]['产品名称'] = $v->buy_name;
                $data[$k]['产品数量'] = $v->buy_number;
                $data[$k]['产品金额'] = $v->buy_money;
                $data[$k]['产品数量'] = $v->express_price;
                $data[$k]['订单ID'] = $v->id;
                $data[$k]['订单编号'] = $v->trade_no;
                $data[$k]['用户留言'] = $v->remark;
            }
            app('excel')->create(date('Y-m-d H:i:s') . '魔都巴士魔币夺宝订单', function ($excel) use ($data) {
                $excel->sheet('魔都巴士魔币夺宝订单', function ($sheet) use ($data) {
                    $sheet->fromArray($data, 'null', 'A1', true, true);
                });
            })->export('xlsx');
        } else {
            $list = $buyOrder->paginate();
        }
        return $this->paginator($list, new UserBuyOrderTransformer());
    }

    function get($id)
    {
        $buyOrder = UserBuyOrder::where('id', $id)->firstOrFail();
        return $this->item($buyOrder, new UserBuyOrderTransformer());
    }

    function delete($id)
    {
        $buyOrder = UserBuyOrder::findOrFail($id);
        $buyOrder->delete();
        return $this->noContent();
    }

    function send($id)
    {
        $content = $this->request->input('content');
        $buyOrder = UserBuyOrder::findOrFail($id);
        $result = $buyOrder->update(['status' => 'delivered', 'content' => $content]);
        if ($result) {
            $data['first'] = '魔币夺宝订单发货啦';
            $data['keyword1'] = $buyOrder->buy_name;
            $data['keyword2'] = $buyOrder->trade_no;
            $data['keyword3'] = $content;
            sendTplMsg('DqSN7SOkz-5JjiPHqdqsutKtTcMV9NTH2jLUnxWSF4E', config('app.url') . '/#/ohistory', $data, $buyOrder->user->openid);
        }
        return $this->created();
    }
}
