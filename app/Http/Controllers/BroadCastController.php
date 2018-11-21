<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/7 0007
 * Time: 18:58
 */

namespace App\Http\Controllers;


class BroadCastController extends Controller
{
    public function send($type, $mediaId, $groupId = 0, $preview = false)
    {
        $openid = $this->request->input('openid');
        $broadcast = getApp($this->user()->cur_appid)->broadcast;
        $extra = ['Text', 'News', 'Voice', 'Image', 'Video'];
        if (!in_array($type, array_keys($extra))) {
            $this->errorBadRequest('params error!');
        }
        $defaultName = "send{$extra[$type]}";
        $costomName = "preview{$extra[$type]}";
        if ($extra[$type] == 'Text') {
            $mediaId = urldecode($mediaId);
        }
        if ($groupId > 0) {
            return $broadcast->$defaultName($mediaId, $groupId);
        }
        if (!empty($openid)) {
            if ($preview) {
                return $broadcast->$costomName($mediaId, $openid);
            }
            if (strpos($openid, ',') !== false) {
                return $broadcast->$defaultName($mediaId, explode(',', $openid));
            }
            return $broadcast->$defaultName($mediaId, $openid);
        }
        return $broadcast->$defaultName($mediaId);
    }

    public function getTags()
    {
        return getApp($this->user()->cur_appid)->user_tag->lists();
    }
}