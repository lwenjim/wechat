<?php

namespace App\Http\Controllers\Admin;
use App\Models\Wechat;

class SettingController extends AdminController
{
    private static $mini_card_img_redis_key = 'mornight:mini_card';
    function setting()
    {
        if ($this->request->isMethod('post')) {
            $data = $this->request->input();
            foreach ($data as $key => $val) {
                setting($key, $val);
            }
            return $this->created();
        } else {
            return setting();
        }
    }

    function minicard()
    {
        if ($this->request->isMethod('get')) {
            return app('redis')->get(self::$mini_card_img_redis_key);
        }
        $image_file_remote = $this->request->input('image');
        $image_file = storage_path('app/upload/' . strtolower(substr($image_file_remote, strrpos($image_file_remote, '/') + 1)));
        if (!is_dir($dirname = dirname($image_file))) {
            mkdir($dirname, 0777, true);
        }
        if (!file_exists($image_file)) {
            if (file_put_contents($image_file, file_get_contents($image_file_remote)) <= 0) {
                return 'fail writed';
            }
        }
        $wechats = getEnableGzh();
        foreach ($wechats as $key => $wechat) {
            $appid = $wechat->appid;
            $material = getApp($appid)->material;
            $result = $material->uploadImage($image_file);
            Wechat::where(['appid' => $appid, 'status' => 1])->update(['media_id' => $result['media_id']]);
        }
        app('redis')->set(self::$mini_card_img_redis_key, $image_file_remote);
        return $this->noContent();
    }
}
