<?php

namespace App\Transformers;

use App\Models\Wechat;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class WechatTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['qrcodes', 'replies', 'staffs', 'users', 'admin'];

    public function transform(Wechat $wechat)
    {
        return $wechat->attributesToArray();
    }

    public function includeQrcodes(Wechat $wechat, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $walks = $wechat->qrcodes()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $wechat->qrcodes()->count();
        return $this->collection($walks, new WeChatQrcodeTransformer())->setMeta(['total' => $total]);
    }

    public function includeReplies(Wechat $wechat, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $walks = $wechat->replies()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $wechat->replies()->count();
        return $this->collection($walks, new WeChatReplyTransformer())->setMeta(['total' => $total]);
    }

    public function includeStaffs(Wechat $wechat, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $walks = $wechat->staffs()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $wechat->staffs()->count();
        return $this->collection($walks, new WeChatStaffTransformer())->setMeta(['total' => $total]);
    }

    public function includeUsers(Wechat $wechat, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $walks = $wechat->users()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get()->map(function ($item) {
            $item->pivot = ['openid' => $item->pivot->openid, 'subscribe' => $item->pivot->subscribe, 'subscribe_time' => $item->pivot->subscribe_time, 'is_default' => $item->pivot->is_default];
            return $item;
        });
        $total = $wechat->users()->count();
        return $this->collection($walks, new UserTransformer())->setMeta(['total' => $total]);
    }

    public function includeAdmin(Wechat $wechat, ParamBag $params = null)
    {
        $walks = $wechat->users()->orderBy('created_at', 'desc')->get()->map(function ($item) {
            $item->pivot = ['openid' => $item->pivot->openid, 'subscribe' => $item->pivot->subscribe, 'subscribe_time' => $item->pivot->subscribe_time, 'is_default' => $item->pivot->is_default];
            return $item;
        });
        $total = $wechat->users()->count();
        return $this->collection($walks, new UserTransformer())->setMeta(['total' => $total]);
    }
}
