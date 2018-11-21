<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/5 0005
 * Time: 19:10
 */

namespace App\Http\Controllers;


use App\Models\Wechat;
use const false;
use function file_exists;
use function info;
use OSS\OssClient;
use OSS\Core\OssException;
use function unlink;
use function usleep;

class StaffController extends Controller
{
    private static function log($token_users_id, $material_type, $material_content, $receive_openids)
    {
        app('redis')->lpush(config('database.redis.keys.0'), \GuzzleHttp\json_encode([
            'token_users_id' => $token_users_id,
            'material_type' => $material_type,
            'material_content' => $material_content,
            'receive_openids' => $receive_openids,
        ]));
    }

    public function sendText($text, $openid, $loging = true)
    {
        $text = urldecode($text);
        $message = new \EasyWeChat\Message\Text(['content' => $text]);
        $SendTextJob = new \App\Jobs\SendTextJob($this->user()->cur_appid, $openid, $message);
        $this->dispatch($SendTextJob->onQueue('message'));
        if ($loging) static::log($this->user()->id, 0, $text, $openid);
    }

    public function sendTextToPart($text, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendText($text, $openid, false);
        }
        static::log($this->user()->id, 0, $text, implode(',', $openids));
        return [count($openids)];
    }

    public function sendImage($mediaId, $openid, $loging = true)
    {
        $message = new \EasyWeChat\Message\Image(['media_id' => $mediaId]);
        $SendImageJob = new \App\Jobs\SendImageJob($this->user()->cur_appid, $openid, $message);
        $this->dispatch($SendImageJob->onQueue('message'));
        if ($loging) static::log($this->user()->id, 3, $mediaId, $openid);
    }

    public function sendImageUrl($url, $openid, $ext = 'jpg')
    {
        $url = urldecode($url);
        $image = storage_path() . '/' . md5($url) . '.' . $ext;
        if (!file_exists($image)) {
            file_put_contents($image, curlFileGetContents($url));
        }
        $redisKey = 'tmp:material:Image:'.$image;
        $redis = app('redis');
        if (empty($result = $redis->get($redisKey))) {
            $result = getApp($this->user()->cur_appid)->material->uploadImage($image);
            $redis->setex($redisKey, 3600, \GuzzleHttp\json_encode($result));
        } else {
            $result = \GuzzleHttp\json_decode($result, true);
        }
        $this->sendImage($result['media_id'], $openid, false);
        static::log($this->user()->id, 3, $url, $openid);
        return [$result, $openid];
    }

    public function sendImageToPart($mediaId, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendImage($mediaId, $openid, false);
        }
        static::log($this->user()->id, 3, $mediaId, implode(',', $openids));
        return [count($openids)];
    }

    public function sendImageUrlToPart($url, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendImageUrl($url, $openid, false);
        }
        static::log($this->user()->id, 3, $url, implode(',', $openids));
        return [count($openids)];
    }

    public function sendVideo($mediaId, $openid, $loging = true)
    {
        $appid = $this->user()->cur_appid;
        $redisKey = 'tmp:material:Video:'.$mediaId;
        $redis = app('redis');
        if (empty($videoInfo = $redis->get($redisKey))) {
            $videoInfo = getApp($appid)->material->get($mediaId);
            $redis->setex($redisKey, 3600, \GuzzleHttp\json_encode($videoInfo));
        } else {
            $videoInfo = \GuzzleHttp\json_decode($videoInfo, true);
        }
        $message = new \EasyWeChat\Message\Video([
            'title' => $videoInfo['title'],
            'media_id' => $mediaId,
            'description' => $videoInfo['description'],
        ]);
        $SendVideoJob = new \App\Jobs\SendVideoJob($appid, $openid, $message);
        $this->dispatch($SendVideoJob->onQueue('message'));
        if ($loging) static::log($this->user()->id, 4, $mediaId, $openid);
    }

    public function sendVideoUrl($url, $openid, $title, $description, $ext = 'mp4')
    {
        $url = urldecode($url);
        $image = storage_path() . '/' . md5($url) . '.' . $ext;
        if (!file_exists($image)) {
            file_put_contents($image, curlFileGetContents($url));
        }
        $redisKey = 'tmp:material:Video:'.$image.$title.$description;
        $redis = app('redis');
        if(empty($result = $redis->get($redisKey))){
            $result = getApp($this->user()->cur_appid)->material->uploadVideo($image, $title, $description);
            $redis->setex($redisKey, 3600, \GuzzleHttp\json_encode($result));
        }else{
            $result = \GuzzleHttp\json_decode($result,true);
        }
        $this->sendVideo($result['media_id'], $openid, false);
        static::log($this->user()->id, 4, $url, $openid);
        return [$result, $openid];
    }

    public function sendVideoToPart($mediaId, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendVideo($mediaId, $openid, false);
        }
        static::log($this->user()->id, 4, $mediaId, implode(',', $openids));
        return [count($openids)];
    }

    public function sendVideoUrlToPart($url, $title, $description, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendVideoUrl($url, $openid, $title, $description);
        }
        static::log($this->user()->id, 4, $url, implode(',', $openids));
        return [count($openids)];
    }

    public function sendVoice($mediaId, $openid, $loging = true)
    {
        $message = new \EasyWeChat\Message\Voice(['media_id' => $mediaId]);
        $SendVoiceJob = new \App\Jobs\SendVoiceJob($this->user()->cur_appid, $openid, $message);
        $this->dispatch($SendVoiceJob->onQueue('message'));
        if ($loging) static::log($this->user()->id, 2, $mediaId, $openid);
    }

    public function sendVoiceUrl($url, $openid, $ext = 'mp3')
    {
        $url = urldecode($url);
        $fullFileName = storage_path() . '/' . md5($url) . '.' . $ext;
        if (!file_exists($fullFileName)) {
            file_put_contents($fullFileName, curlFileGetContents($url));
        }
        $redisKey = 'tmp:material:voice:'.$fullFileName;
        $redis = app('redis');
        if(empty($result = $redis->get($redisKey))){
            $result = getApp($this->user()->cur_appid)->material->uploadVoice($fullFileName);
            $redis->setex($redisKey, 3600, \GuzzleHttp\json_encode($result));
        }else{
            $result = \GuzzleHttp\json_decode($result,true);
        }
        $this->sendVoice($result['media_id'], $openid, false);
        static::log($this->user()->id, 2, $url, $openid);
        return [$result, $openid];
    }

    public function sendVoiceToPart($mediaId, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendVoice($mediaId, $openid, false);
        }
        static::log($this->user()->id, 2, $mediaId, implode(',', $openids));
        return [count($openids)];
    }

    public function sendVoiceUrlToPart($url, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendVoiceUrl($url, $openid, false);
        }
        static::log($this->user()->id, 2, $url, implode(',', $openids));
        return [count($openids)];
    }

    public function sendNews($mediaId, $openid, $loging = true)
    {
        $redisKey = 'tmp:material:news:'.$mediaId;
        $redis = app('redis');
        if(empty($newsInfos = $redis->get($redisKey))){
            $newsInfos = getApp($this->user()->cur_appid)->material->get($mediaId);
            $redis->setex($redisKey, 3600, \GuzzleHttp\json_encode($newsInfos));
        }else{
            $newsInfos = \GuzzleHttp\json_decode($newsInfos, true);
        }
        $message = [];
        foreach ($newsInfos['news_item'] as $newsInfo) {
            $message[] = new \EasyWeChat\Message\News([
                'title' => $newsInfo['title'],
                'description' => $newsInfo['digest'],
                'url' => $newsInfo['url'],
                'image' => $newsInfo['thumb_url'],
            ]);
        }
        $SendNewsJob = new \App\Jobs\SendNewsJob($this->user()->cur_appid, $openid, $message);
        $this->dispatch($SendNewsJob->onQueue('sign'));
        if ($loging) static::log($this->user()->id, 1, $mediaId, $openid);
        $redisKey = 'wx:material:news';
        if (!$redis->hexists($redisKey, $mediaId)) {
            foreach ($newsInfos['news_item'] as $key => $newsInfo) {
                if (false === ($url = static::mediaIdTransferOssUrl($this->user()->cur_appid, $newsInfo['thumb_media_id']))) continue;
                $newsInfos['news_item'][$key] = ['imageurl' => $url] + $newsInfo;
                usleep(100);
            }
            $wechatId = Wechat::where(['appid' => $this->user()->cur_appid])->value('id');
            \App\Models\WXMaterialNewsSendedHistory::create([
                'wechat_id' => $wechatId,
                'media_id' => $mediaId,
                'content' => (array)$newsInfos['news_item'],
            ]);
            $redis->hset($redisKey, $mediaId, date('Y-m-d H:i:s'));
        }
    }

    public static function mediaIdTransferOssUrl($appid, $mediaId)
    {
        $content = getApp($appid)->material->get($mediaId);
        $filename = '/tmp/' . md5($content);
        file_put_contents($filename, $content);
        try {
            $accessKeyId = config('filesystems.disks.aliyun.accessKeyId');
            $accessKeySecret = config('filesystems.disks.aliyun.accessKeySecret');
            $endpoint = config('filesystems.disks.aliyun.endpoint');
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, true);
            $bucket = config('filesystems.disks.aliyun.bucket');
            $path = 'mediabook/' . date('Y/m/d') . '/' . $mediaId . '.jpg';
            $res = (array)$ossClient->uploadFile($bucket, $path, $filename);
            file_exists($filename) && unlink($filename);
            return $res['info']['url'];
        } catch (OssException $e) {
            file_exists($filename) && unlink($filename);
            info($e->getMessage());
            return false;
        }
    }

    public function sendNewsToPart($mediaId, $sex = null, $province = null, $city = null, $tagId = 0)
    {
        set_time_limit(0);
        $openids = $this->getPartOpenid($sex, $province, $city, $tagId);
        foreach ($openids as $openid) {
            $this->sendNews($mediaId, $openid, false);
        }
        static::log($this->user()->id, 1, $mediaId, implode(',', $openids));
        return [count($openids)];
    }

    private function getPartOpenid($sex, $province, $city, $tagId)
    {
        $appid = $this->user()->cur_appid;
        $wechat = Wechat::where('appid', $appid)->first();
        $userModel = $wechat->users()->wherePivot('subscribe', 1);
        if (in_array($sex, [1, 2])) {
            $userModel = $userModel->where('sex', $sex);
        }
        if (!empty($province)) {
            $userModel = $userModel->where('province', $province);
        }
        if (!empty($city)) {
            $city = urldecode($city);
            $userModel = $userModel->where('city', $city);
        }
        $openids = $userModel->pluck('wechat_user.openid')->toArray();
        if ($tagId > 0) {
            $usersOfTag = getApp($appid)->user_tag->usersOfTag($tagId);
            $openids = array_intersect($openids, $usersOfTag['data']['openid']);
        }
        $openids = array_intersect($openids, getActiveUser($appid));
        return $openids;
    }

    public function fetchSendLog($tokenUsersId = 0, $materialType = -1)
    {
        $builder = new \App\Models\UserStaffSendLog;
        if (preg_match("/^\d*$/", $tokenUsersId) && $tokenUsersId > 0) {
            $tokenUsersId = explode(',',$tokenUsersId);
            $builder = $builder->whereIn('token_users_id', $tokenUsersId);
        }
        if (preg_match("/^\d$/", $materialType) && in_array($materialType, [0, 1, 2, 3, 4])) {
            $materialType = explode(',', $materialType);
            $builder = $builder->whereIn('material_type', $materialType);
        }
        $logs = $builder->orderBy('id','desc')->paginate(30);
        return $this->collection($logs, new \App\Transformers\UserStaffSendLogTransformer());
    }
}