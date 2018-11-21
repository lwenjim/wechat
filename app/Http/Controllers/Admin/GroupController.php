<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Transformers\GroupTransformer;

class GroupController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $Groups = Group::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($Groups, new GroupTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:20'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        Group::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $Group = Group::find($id);
        if (!$Group) {
            return $this->errorNotFound();
        }
        return $this->item($Group, new GroupTransformer());
    }

    function delete($id)
    {
        $Group = Group::find($id);
        if (!$Group) {
            return $this->errorNotFound();
        }
        $Group->delete();
        return $this->noContent();
    }
}
