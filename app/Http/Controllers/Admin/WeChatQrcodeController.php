<?php

namespace App\Http\Controllers\Admin;

use App\Transformers\WeChatQrcodeTransformer;
use App\Models\WeChatQrcode;

class WeChatQrcodeController extends AdminController
{
    public function index()
    {
        $where['name'] = $this->request->get('name');
        $qrcodes = WeChatQrcode::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
        })->withCount('users')->orderBy('id', 'desc')->paginate();
        return $this->paginator($qrcodes, new WeChatQrcodeTransformer());
    }

    public function get($id)
    {
        $qrcode = WeChatQrcode::find($id);
        if (!$qrcode) {
            return $this->errorNotFound();
        }
        return $this->item($qrcode, new WeChatQrcodeTransformer());
    }

    public function post()
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:50',
            'type' => 'required|integer',
            'wechat_id' => 'required'
        ]);
        $validator->sometimes('expire', 'required|integer|between:60,2592000', function ($data) {
            return $data['type'] == 1;
        });
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $qrcode = WeChatQrcode::create($data);
        $wechatQrcode = getApp($qrcode->wechat->appid)->qrcode;
        if ($qrcode->type == 1) {
            $scene = 100000000 + $qrcode->id;
            $qrcode_result = $wechatQrcode->temporary($scene, $qrcode->expire);
        } else {
            $scene = md5("$qrcode->id");
            $qrcode_result = $wechatQrcode->forever($scene);
        }
        $qrcode->update(['scene' => $scene, 'url' => $qrcode_result->url]);
        return $this->created();
    }

    public function put($id)
    {
        $qrcode = WeChatQrcode::findOrFail($id);
        $qrcode->update($this->request->except(['id', 'expire', 'scene', 'url']));
        return $this->created();
    }

    public function delete($id)
    {
        $qrcode = WeChatQrcode::findOrFail($id);
        $qrcode->delete();
        return $this->noContent();
    }
}