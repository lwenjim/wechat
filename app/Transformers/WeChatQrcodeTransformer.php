<?php

namespace App\Transformers;

use App\Models\WeChatQrcode;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class WeChatQrcodeTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['users'];

    public function transform(WeChatQrcode $qrcode)
    {
        return $qrcode->attributesToArray();
    }

    public function includeUsers(WeChatQrcode $qrcode, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $users = $qrcode->users()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $qrcode->users()->count();
        return $this->collection($users, new WeChatQrcodeUserTransformer())->setMeta(['total' => $total]);
    }
}
