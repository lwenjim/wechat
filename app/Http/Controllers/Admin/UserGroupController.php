<?php

namespace App\Http\Controllers\Admin;

use App\Models\UserGroup;
use App\Transformers\UserGroupTransformer;

class UserGroupController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $UserGroups = UserGroup::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($UserGroups, new UserGroupTransformer());
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
        UserGroup::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $UserGroup = UserGroup::find($id);
        if (!$UserGroup) {
            return $this->errorNotFound();
        }
        return $this->item($UserGroup, new UserGroupTransformer());
    }

    function delete($id)
    {
        $UserGroup = UserGroup::find($id);
        if (!$UserGroup) {
            return $this->errorNotFound();
        }
        $UserGroup->delete();
        return $this->noContent();
    }
}
