<?php

namespace App\Transformers;

use App\Models\UserOrder;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class UserOrderTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'express', 'products', 'logs', 'comments', 'afters'];

    public function transform(UserOrder $userOrder)
    {
        return $userOrder->attributesToArray();
    }

    public function includeUser(UserOrder $userOrder)
    {
        return $this->item($userOrder->user()->select('id', 'openid', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeExpress(UserOrder $userOrder)
    {
        if ($userOrder->express) {
            return $this->item($userOrder->express()->select('id', 'name')->first(), new ExpressTransformer());
        } else {
            return null;
        }
    }

    public function includeProducts(UserOrder $userOrder)
    {
        return $this->collection($userOrder->products()->orderBy('updated_at', 'desc')->get(), new UserOrderProductTransformer());
    }

    public function includeAfters(UserOrder $userOrder)
    {
        return $this->collection($userOrder->afters()->orderBy('id', 'desc')->get(), new UserOrderAfterTransformer());
    }

    public function includeLogs(UserOrder $userOrder, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $logs = $userOrder->logs()->orderBy('id', 'desc')->take($row)->skip($offset)->get();
        $total = $userOrder->logs()->count();
        return $this->collection($logs, new UserOrderLogTransformer())->setMeta(['total' => $total]);
    }

    public function includeComments(UserOrder $userOrder, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $comments = $userOrder->comments()->orderBy('id', 'desc')->take($row)->skip($offset)->get();
        $total = $userOrder->comments()->count();
        return $this->collection($comments, new UserOrderCommentTransformer())->setMeta(['total' => $total]);
    }
}
