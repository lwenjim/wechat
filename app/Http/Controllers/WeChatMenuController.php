<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialText;
use EasyWeChat\Core\Http;

class WeChatMenuController extends Controller
{
    function __construct(Request $request)
    {
        parent::__construct($request);
        if (empty($this->user()->cur_appid)) {
            return $this->error('appid empty',400);
        }
    }

    public function index()
    {
        return getApp($this->user()->cur_appid)->menu->current();
    }

    public function post()
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'menu' => 'required|string'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $menus = json_decode($data['menu'], true);
        $appid = $this->user()->cur_appid;
        $app = getApp($appid);
        $menu = $app->menu;
        $menu->add($menus[0]);

        $tag = $app->user_tag;
        $tagId = getTagIdFordoesntExistCustomMenu($tag);
        $matchRule = ["tag_id" => $tagId];
        $menu->add($menus[1], $matchRule);
        return $this->noContent();
    }

    public function delete($id)
    {
        return getApp($this->user()->cur_appid)->menu->destroy($id);
    }

    public function material($type, $offset = 0, $count = 20)
    {
        return getApp($this->user()->cur_appid)->material->lists($type, $offset, $count);
    }

    public function material_upload_text()
    {
        $txt = $this->request->input('content');
        $md5 = md5($txt);
        $row = MaterialText::where(['key' => $md5])->first();
        if ($row) {
            return $md5;
        }
        $model = new MaterialText;
        $model->txt = $txt;
        $model->key = $md5;
        $model->save();
        return $model->key;
    }

    public function material_upload_image()
    {
        $filename = storage_path() . '/' . md5($this->request->file('image')) . '.jpg';
        file_put_contents($filename, file_get_contents($this->request->file('image')));
        $result = getApp($this->user()->cur_appid)->material->uploadImage($filename);
        unlink($filename);
        return $result;
    }

    public function material_upload_news()
    {
        $content = $this->request->input('data');
        $newsArray = [];
        foreach ($content['title'] as $k => $v) {
            $news = new \EasyWeChat\Message\Article([
                'title' => $v,
                'digest' => $content['digest'][$k],
                'content_source_url' => $content['content_source_url'][$k],
                'thumb_media_id' => $content['thumb_media_id'][$k],
                'content' => $content['content'][$k],
            ]);
            $newsArray[$k] = $news;
        }
        $result = getApp($this->user()->cur_appid)->material->uploadArticle($newsArray);
        return $result;
    }

    public function material_upload_voice()
    {
        $remoteName = $this->request->file('voice');
        $localName = storage_path() . '/' . md5($remoteName) . '.mp3';
        file_put_contents($localName, file_get_contents($remoteName));
        $result = getApp($this->user()->cur_appid)->material->uploadVoice($localName);
        unlink($localName);
        return $result;
    }

    public function material_upload_video()
    {
        $remoteName = $this->request->file('video');
        $title = $this->request->input('title');
        $digest = $this->request->input('digest');
        $localName = storage_path() . '/' . md5($remoteName) . '.mp4';
        file_put_contents($localName, file_get_contents($remoteName));
        $result = getApp($this->user()->cur_appid)->material->uploadVideo($localName, $title, $digest);
        unlink($localName);
        return $result;
    }

    public function material_upload_get($mediaId)
    {
        $row = MaterialText::where(['key' => $mediaId])->first();
        if ($row) {
            return $row->txt;
        }
        return getApp($this->user()->cur_appid)->material->get($mediaId);
    }

    public function get_miniprogram_info()
    {
        $appid = $this->user()->cur_appid;
        if (!$appid) {
            return $this->errorBadRequest('为绑定公众号');
        }
        $token = getApp($appid)->access_token->getToken();
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/wxamplinkget?access_token=" . $token;
        $http = new Http();
        return $http->post($url, ['BTime' => 'flag'])->getBody();
    }
}