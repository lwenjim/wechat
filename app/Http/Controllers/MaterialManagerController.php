<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/3 0003
 * Time: 11:47
 */

namespace App\Http\Controllers;


class MaterialManagerController extends Controller
{
    public function index($type, $offset, $count)
    {
        $app = getApp($this->user()->cur_appid);
        $list = $app->material->lists($type, $offset, $count);
        if ($type == 'video') {
            $list['item'] = array_map(function ($item) use ($app) {
                $resource = $app->material->get($item['media_id']);
                return [$resource, $item];
            }, $list['item']);
        }
        return $list;
    }

    public function get($material_id)
    {
        return getApp($this->user()->cur_appid)->material->get($material_id);
    }

    public function post($type)
    {
        $app = getApp($this->user()->cur_appid);
        $result = [];
        switch ($type) {
            case 'image':
            case 'voice':
            case 'thumb':
            case 'video':
                $uploadFile = $this->request->file('file');
                $image = $uploadFile->move(storage_path(), $uploadFile->getClientOriginalName());
                if ($type == 'video') {
                    $title = $this->request->input('title');
                    $desc = $this->request->input('desc');
                    if (!$title || !$desc) return $this->errorBadRequest('error params');
                    $result = $app->material->uploadVideo($image, $title, $desc);
                } elseif($type == 'thumb'){
                    $result = $app->material->uploadThumb($image);
                }elseif ($type == 'voice') {
                    $result = $app->material->uploadVoice($image);
                } else {
                    $result = $app->material->uploadImage($image);
                }
                if (file_exists($image)) {
                    unlink($image);
                }
                break;
            case 'news':
                $article = $this->request->input('article');
                $article = static::jsonToArticle($app, $article);
                if ($this->request->has('media_id')) {
                    $mediaId = $this->request->input('media_id');
                    $index = (int)$this->request->input('index');
                    if (!$mediaId) return $this->errorBadRequest('empty media_id');
                    $result = $app->material->updateArticle($mediaId, $article, $index);
                } else {
                    $result = $app->material->uploadArticle($article);
                }
                break;
        }
        return $result;
    }

    private static function jsonToArticle($app,$article)
    {
        $article = (object)$article;
        if (!isset($article->thumb_media_id)) {
            $row = [];
            foreach ($article as $art) {
                $row[] = static::jsonToArticle($app, $art);
            }
            return $row;
        } else {
            return new \EasyWeChat\Message\Article([
                'thumb_media_id' => $article->thumb_media_id,
                'author' => $article->author,
                'title' => $article->title,
                'content' => static::handleWxNewContent($app, $article->content),
                'digest' => $article->digest,
                'source_url' => $article->source_url,
                'show_cover' => $article->show_cover,
            ]);
        }
    }

    public function stat()
    {
        return $stats = getApp($this->user()->cur_appid)->material->stats();
    }

    public function delete($material_id)
    {
        getApp($this->user()->cur_appid)->material->delete($material_id);
    }

    public function trade($mediaId){
        $app = getApp($this->user()->cur_appid);
        $resource = $app->material->get($mediaId);
        return $resource;
    }

    private static function handleWxNewContent($app,$content)
    {
        return preg_replace_callback("/(<img.*?src=\")([^\"]+)(\".*?\/>)/",function($match) use ($app){
            $url = $match[2];
            $fileName = storage_path().'/'.md5($url).'.jpg';
            file_put_contents($fileName,curlFileGetContents($url));
            $result = $app->material->uploadImage($fileName);
            return $match[1].$result['url'].$match[3];
        },$content);
    }
}