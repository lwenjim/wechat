<?php

namespace App\Http\Controllers\Admin;

use App\Transformers\AppCodeTransformer;
use App\Models\AppCode;

class AppCodeController extends AdminController
{
    public function index()
    {
        $where['name'] = $this->request->get('name');
        $appcodes = AppCode::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
        })->withCount('users')->orderBy('id', 'desc')->paginate();
        return $this->paginator($appcodes, new AppCodeTransformer());
    }

    public function get($id)
    {
        $appcode = AppCode::find($id);
        if (!$appcode) {
            return $this->errorNotFound();
        }
        return $this->item($appcode, new AppCodeTransformer());
    }

    public function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:50',
            'page' => 'required|string',
            'color' => 'required|string',
            'width' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $appcode = AppCode::updateOrCreate(['id' => $id], $data);
        $scene = $this->request->input('scene');
        if (!$scene) {
            $scene = md5('appcode' . $appcode->id);
        }
        $url = getAppCode($scene, $appcode->page, $appcode->width, $appcode->color);
        $appcode->update(['scene' => $scene, 'url' => $url]);
        return $this->created();
    }

    public function delete($id)
    {
        $appcode = AppCode::findOrFail($id);
        $appcode->delete();
        return $this->noContent();
    }
}