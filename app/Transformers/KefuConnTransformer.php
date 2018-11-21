<?php

namespace App\Transformers;

use App\Models\KefuConn;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class KefuConnTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['msgs', 'user'];

    public function transform(KefuConn $kefuConn)
    {
        return $kefuConn->attributesToArray();
    }

    public function includeMsgs(KefuConn $kefuConn, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $msgs = $kefuConn->msgs()->orderBy('updated_at', 'desc')->take($row)->skip($offset)->get();
        $total = $kefuConn->msgs()->count();
        return $this->collection($msgs, new KefuConnMsgTransformer())->setMeta(['total' => $total]);
    }

    public function includeUser(KefuConn $kefuConn)
    {
        $user = $kefuConn->user()->select('id', 'nickname', 'headimgurl')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        }
    }
}
