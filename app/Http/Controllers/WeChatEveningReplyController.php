<?php

namespace App\Http\Controllers;

use App\Models\Wechat;
use App\Transformers\WeChatReplyTransformer;
use App\Models\WeChatReply;
use function var_dump;

class WeChatEveningReplyController extends Controller
{
    function set()
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'reply' => 'required|string|in:text,news',
            'content' => 'required',
        ]);
        $appid = $this->user()->cur_appid;
        $data['wechat_id'] = Wechat::where(['appid' => $appid])->value('id');
        $data['name'] = '公众号授权第三方平台-晚读提醒';
        $data['receive'] = 'keyword';
        $data['keyword'] = $data['name'];
        $data['status'] = 1;
        unset($data['api_token']);
        if (!$data['wechat_id']) {
            return $this->errorBadRequest('公众号不存在');
        }
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        WeChatReply::updateOrCreate(['wechat_id' => $data['wechat_id'], 'keyword' => '公众号授权第三方平台-晚读提醒'], $data);
        return $this->created();
    }

    function get()
    {
        $appid = $this->user()->cur_appid;
        if (!$appid) {
            return $this->errorNotFound();
        }
        $wechat_id = Wechat::where(['appid' => $appid])->value('id');
        $reply = WeChatReply::where(['wechat_id' => $wechat_id, 'keyword' => '公众号授权第三方平台-晚读提醒'])->first();
        if (!$reply) {
            return $this->errorNotFound();
        }
        return $this->item($reply, new WeChatReplyTransformer());
    }

    function restore()
    {
        $appid = $this->user()->cur_appid;
        if (!$appid) {
            return $this->errorNotFound();
        }
        useRemainSettingInfoForThirdGzh($appid, '公众号授权第三方平台-晚读提醒');
        return $this->created();
    }
}