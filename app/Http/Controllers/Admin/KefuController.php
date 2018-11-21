<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Kefu;
use App\Models\KefuConn;
use App\Transformers\KefuTransformer;
use App\Transformers\KefuConnTransformer;

class KefuController extends AdminController
{
    function index()
    {
        $where['status'] = $this->request->get('status');
        $where['name'] = $this->request->get('name');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $list = Kefu::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($list, new KefuTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:20',
            'users' => 'required'
        ], ['name.required' => '名称不能为空!']);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $kefu = Kefu::updateOrCreate(['id' => $id], array_except($data, ['users']));
        if (is_array($data['users']) && !empty($data['users'])) {
            $kefu->users()->sync($data['users']);
        }
        if (isset($data['is_default']) && $data['is_default'] == 1) {
            Kefu::where('id', '<>', $kefu->id)->update(['is_default' => 0]);
        }
        return $this->created();
    }

    function get($id)
    {
        $kefu = Kefu::find($id);
        if (!$kefu) {
            return $this->errorNotFound();
        }
        return $this->item($kefu, new KefuTransformer());
    }

    function delete($id)
    {
        $kefu = Kefu::find($id);
        if (!$kefu) {
            return $this->errorNotFound();
        }
        $kefu->delete();
        return $this->noContent();
    }

    function msgGet($id)
    {
        $start = $this->request->get('start', 0);
        $row = $this->request->get('row', 25);
        $keyword = $this->request->get('keyword');
        $conns = KefuConn::when($keyword, function ($query) use ($keyword) {
            $user_ids = User::where(function ($query) use ($keyword) {
                if (is_numeric($keyword)) {
                    $query->where('id', $keyword);
                } else {
                    $query->orWhere('openid', 'like', '%' . $keyword . '%')->orWhere('openid', 'like', '%' . $keyword . '%')->orWhere('nickname', 'like', '%' . $keyword . '%');
                }
            })->orderBy('updated_at', 'desc')->pluck('id')->toArray();
            return $query->whereIn('user_id', $user_ids);
        })->withCount(['msgs' => function ($query) {
            $query->where('is_read', 0);
        }])->where('kefu_id', $id)->skip($start)->take($row)->orderBy('updated_at', 'desc')->get();
        return $this->collection($conns, new KefuConnTransformer());
    }

    function msgPut($id)
    {
        $conn = KefuConn::findOrFail($id);
        $conn->msgs()->where('is_read', 0)->update(['is_read' => 1]);
        return $this->item($conn, new KefuConnTransformer());
    }

    function msgPost($id)
    {
        $content = $this->request->input('content');
        $type = $this->request->input('type', 'text');
        $origin = $this->request->input('origin', 1);
        $user_id = $this->request->input('user_id');
        $openid = $this->request->input('openid');
        $conn = KefuConn::updateOrCreate(['user_id' => $user_id, 'kefu_id' => $id], ['updated_at' => date('Y-m-d H:i:s')]);
        if ($conn && sendStaffMsg($type, $content, $openid, $origin)) {
            $conn->msgs()->create(['user_id' => $this->user()->id, 'openid' => $openid, 'type' => $type, 'code' => 2, 'origin' => $origin, 'content' => $content]);
        }
        return $this->created();
    }
}
