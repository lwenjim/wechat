<?php

namespace App\Http\Controllers\Admin;

class WeChatMenuController extends AdminController
{
    function index()
    {
        return getApp()->menu->current();
    }

    function post()
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'menu' => 'required|string'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        return getApp()->menu->add(json_decode($data['menu'], true));
    }

    function delete($id)
    {
        return getApp()->menu->destroy($id);
    }

    function material($type, $offset = 0, $count = 20)
    {
        return app('wechat')->material->lists($type, $offset, $count);
    }
}