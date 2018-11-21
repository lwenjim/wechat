<?php

namespace App\Transformers;

use App\Models\Kefu;
use League\Fractal\TransformerAbstract;

class KefuTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['conns', 'users'];

    public function transform(Kefu $kefu)
    {
        return $kefu->attributesToArray();
    }

    public function includeConns(Kefu $kefu)
    {
        return $this->collection($kefu->conns()->get(), new KefuConnTransformer());
    }

    public function includeUsers(Kefu $kefu)
    {
        return $this->collection($kefu->users()->select('id', 'nickname', 'headimgurl')->get()->map(function ($item) {
            $item->pivot = ['realname' => $item->pivot->realname];
            return $item;
        }), new UserTransformer());
    }
}
