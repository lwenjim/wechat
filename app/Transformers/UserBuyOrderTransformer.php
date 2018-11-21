<?php

namespace App\Transformers;

use App\Models\UserBuyOrder;
use League\Fractal\TransformerAbstract;

class UserBuyOrderTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'buy'];

    public function transform(UserBuyOrder $buyOrder)
    {
        return $buyOrder->attributesToArray();
    }

    public function includeUser(UserBuyOrder $buyOrder)
    {
        return $this->item($buyOrder->user()->select('id', 'openid', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeBuy(UserBuyOrder $buyOrder)
    {
        $buy = $buyOrder->buy()->select('id', 'name', 'images', 'number', 'start_time', 'end_time', 'coin', \DB::raw("(select SUM(user_buy_order.buy_number) from user_buy_order where buy.id = user_buy_order.buy_id) as orders_count"))->first();
        if ($buy) {
            return $this->item($buy, new BuyTransformer());
        }
    }
}
