<?php

namespace App\Transformers;

use App\Models\Buy;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class BuyTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'orders', 'content'];

    public function transform(Buy $buy)
    {
        return $buy->attributesToArray();
    }

    public function includeUser(Buy $buy)
    {
        return $this->item($buy->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeOrders(Buy $buy, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $orders = $buy->orders()->select('id', 'user_id', 'status', 'buy_id', 'buy_number', 'buy_coin', 'created_at')->whereBetween('created_at', [$buy->start_time, $buy->end_time])->orderBy('status', 'asc')->orderBy('created_at', 'desc');
        return $this->collection($orders->take($row)->skip($offset)->get(), new UserBuyOrderTransformer())->setMeta(['total' => $orders->count()]);
    }

    public function includeContent(Buy $buy)
    {
        return $this->item($buy->content, new BuyContentTransformer());
    }
}
