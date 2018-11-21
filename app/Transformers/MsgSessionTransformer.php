<?php

namespace App\Transformers;

use App\Models\MsgSession;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class MsgSessionTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['msgs', 'user'];

    public function transform(MsgSession $session)
    {
        return $session->attributesToArray();
    }

    public function includeUser(MsgSession $session)
    {
        return $this->item($session->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }

    public function includeMsgs(MsgSession $session, ParamBag $params = null)
    {
        $limit = $params->get('limit');
        if($limit){
            list($row, $offset) = $limit;
        }else{
            $row = 20;
            $offset = 0;
        }
        return $this->collection($session->msgs()->select('id', 'session_id', 'user_id', 'type', 'code', 'content', 'created_at')->take($row)->skip($offset)->orderBy('id', 'desc')->get(), new MsgTransformer());
    }
}
