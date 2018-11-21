<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use App\Models\User;
use App\Models\Wechat;
use App\Models\Achievement;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class Card
{
    const ACHIEVE_DIR = '/achieves/'; //成就卡背景图保存路径
    const CARD_DIR = '/cards/'; //保存路径
    const HEAD_DIR = '/heads/'; //头像保存路径
    const FONT_DIR = '/fonts/'; //字体
    const THIRD_PARTY_PREFIX = '/web/www/mornight/api/storage/achieves';
    const OFFICIAL_PARTY_PREFIX = '/web/www/mornight/api/storage/achieves/';
    const OFFICIAL_APPID = 'wxa7852bf49dcb27d7';

    public static function achieve($user, $city, $days, $day, $time, $appid, $type = 'primary', $origin = 'sign')
    {
        $date = date('Y-m-d');
        if ($origin == 'tomorrow') $date = date('Y-m-d', strtotime('+1 day'));
        $achieve = storage_path() . self::CARD_DIR . 'achieve-' . $date . '-' . $user->id . '-' . $type . '-' . $appid . '-' . $origin . '.jpg';
        if (file_exists($achieve) && $origin != 'tomorrow') return $achieve;
        $wechat = Wechat::select('id', 'appid', 'type', 'qrcode')->where(['appid' => $appid, 'status' => 1])->first();
        $achieveModel = Achievement::select('image', 'font', 'width', 'height', 'content')
            ->whereIn('appid', [$wechat->appid, self::OFFICIAL_APPID])
            ->where(['default' => 0, 'type' => $type, 'date' => $date])
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get();
        $achieveModel = !empty($achieveModel) ? $achieveModel[0] : false;
        if (false === $achieveModel) {
            info($wechat->appid . '没有设置自己的成就卡，' . self::OFFICIAL_APPID . '也没有设置成就卡。');
            return false;
        }
        $achieve_background = self::THIRD_PARTY_PREFIX . '/' . $wechat->appid . '/achive-bg-' . $date . '.jpg';
        if (!file_exists($achieve_background) || !is_readable($achieve_background)) {
            if (!is_dir($dirNameAchieveBackground = dirname($achieve_background))) mkdir($dirNameAchieveBackground, 0777, true);
            if (!file_put_contents($achieve_background, file_get_contents($achieveModel->image))) {
                info('failed to download :' . $achieveModel->image);
                return false;
            }
        }
        $achieveModel->font = 'http://img.modubus.com/achievement/2017/10/23/苹方黑体-准-简.ttf';
        $font_file = storage_path() . self::FONT_DIR . strtolower(substr($achieveModel->font, strrpos($achieveModel->font, '/') + 1));
        if (!is_dir($parent_dir = dirname($font_file))) mkdir($parent_dir, 777, true);
        if (!file_exists($font_file)) {
            if (file_put_contents($font_file, self::file_get_contents_curl($achieveModel->font)) <= 0) {
                info($date . '成就卡字体不存在无法生成！');
                return false;
            }
        }
        $content = $achieveModel->content;
        Image::configure(['driver' => 'imagick']);
        $img = Image::make($achieve_background);
        $img->resize($achieveModel->width, $achieveModel->height);
        if (isset($content['headimgurl']) && file_exists($head = self::getLocalFileName($user))) {
            $headImg = Image::make($head);
            $headImg->resize($content['headimgurl']['width'], $content['headimgurl']['height']);
            $headImgBg = Image::make(storage_path('images/bg.png'));
            $headImgBg->resize($content['headimgurl']['width'] + 10, $content['headimgurl']['height'] + 10);
            $headImgBg->insert($headImg, 'center');
            $img->insert($headImgBg, 'top-left', $content['headimgurl']['left'], $content['headimgurl']['top']);
        }
        if (isset($content['nickname'])) {
            $img->text($user->nickname, $content['nickname']['left'], $content['nickname']['top'], function ($font) use ($content, $font_file) {
                $font->file($font_file);
                $font->size($content['nickname']['size']);
                $font->color($content['nickname']['color']);
            });
        }
        if (isset($content['signtime'])) {
            $img->text($time, $content['signtime']['left'], $content['signtime']['top'], function ($font) use ($content, $font_file) {
                $font->file($font_file);
                $font->size($content['signtime']['size']);
                $font->color($content['signtime']['color']);
            });
        }
        if (isset($content['signcity'])) {
            $img->text($city, $content['signcity']['left'], $content['signcity']['top'], function ($font) use ($content, $font_file) {
                $font->file($font_file);
                $font->size($content['signcity']['size']);
                $font->color($content['signcity']['color']);
            });
        }
        if (isset($content['signday'])) {
            $img->text('连续早起第' . $day . '天', $content['signday']['left'], $content['signday']['top'], function ($font) use ($content, $font_file) {
                $font->file($font_file);
                $font->size($content['signday']['size']);
                $font->color($content['signday']['color']);
            });
        }
        if (isset($content['signdays'])) {
            $img->text('今年累计早起第' . $days . '天', $content['signdays']['left'], $content['signdays']['top'], function ($font) use ($content, $font_file) {
                $font->file($font_file);
                $font->size($content['signdays']['size']);
                $font->color($content['signdays']['color']);
            });
        }
        if (isset($content['qrcode'])) {
            $imgurl = $wechat->qrcode;
            if ($wechat->type == 2) {
                $qrCodeUrl = getApp($wechat->appid)->qrcode->temporary($user->id, 7 * 24 * 3600)->url;
                $qrCode = new \SimpleSoftwareIO\QrCode\BaconQrCodeGenerator();
                $imgurl = $qrCode->format('png')->margin(0)->size(600)->generate($qrCodeUrl);
            }
            $qrImg = Image::make($imgurl);
            $qrImg->resize($content['qrcode']['width'], $content['qrcode']['height']);
            $qrImgBg = Image::make(storage_path('images/bg.png'));
            $qrImgBg->resize($content['qrcode']['width'] + 10, $content['qrcode']['height'] + 10);
            $qrImgBg->insert($qrImg, 'center');
            $img->insert($qrImgBg, 'top-left', $content['qrcode']['left'], $content['qrcode']['top']);
        }
        if (!is_dir($parent_dir = dirname($achieve))) mkdir($parent_dir, 0644, true);
        $img->save($achieve, 60);
        chmod($achieve,0777);
        return $achieve;
    }

    public static function getLocalFileName($user)
    {
        $redis = app('redis');
        $key = 'headimgurl:' . $user->id;
        $filename = storage_path() . self::HEAD_DIR . $user->id . '.jpg';
        $defaultFileName = storage_path('images/head-' . !empty($user->sex) ? $user->sex : mt_rand(1, 2) . '.png');
        if (!file_exists($filename) || $redis->get($key) != $user->headimgurl) {
            try {
                $downObj = new \GuzzleHttp\Client(['verify' => false]);
                $response = $downObj->get($user->headimgurl, ['save_to' => $filename]);
                if ($response->getStatusCode() != 200) {
                    info($user->headimgurl . "\t下载失败！");
                    $filename = null;
                }
            } catch (\Exception $e) {
                $filename = null;
            }
            $redis->setex($key, 3600, $user->headimgurl);
        }
        return $filename ?: $defaultFileName;
    }

    public static function file_get_contents_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
