<?php

namespace App\Http\Controllers;

use App\Models\UserBuyOrder;
use App\Models\User;
use App\Transformers\UserBuyOrderTransformer;
use Illuminate\Support\Facades\DB;

class UserBuyOrderController extends Controller
{
    function index()
    {
        $where['user_id'] = $this->user()->id;
        $where['status'] = $this->request->get('status');
        $where['keyword'] = $this->request->get('keyword');
        $where['pay_type'] = $this->request->get('pay_type');
        $where['pay_status'] = $this->request->get('pay_status');
        $where['export'] = $this->request->get('export', 0);
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $buyOrder = UserBuyOrder::where(function ($query) use ($where) {
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
            $query->where('user_id',$where['user_id']);
        })->orderBy($order_field, $order_type);
        $list = $buyOrder->paginate();
        foreach ($list as $k=>$v){
            if($this->user()->group_id>0){
                $list[$k]['total_cost'] = $v['buy_coin'] + $v['express_price'];
            }else{
                $list[$k]['total_cost'] = $v['market_price'] + $v['express_price'];
            }
        }
        return $this->paginator($list, new UserBuyOrderTransformer());
    }

    function get($id)
    {
        $buyOrder = UserBuyOrder::where('id', $id)->firstOrFail();
        if($this->user()->group_id>0){
            $buyOrder['total_cost'] = $buyOrder['buy_coin'] + $buyOrder['express_price'];
        }else{
            $buyOrder['total_cost'] = $buyOrder['market_price'] + $buyOrder['express_price'];
        }
        return $this->item($buyOrder, new UserBuyOrderTransformer());
    }
}
