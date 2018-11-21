<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Transformers\TagTransformer;

class TagController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $productTags = Tag::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            } else {
                $query->where('parent_id', 0);
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($productTags, new TagTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:255'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        Tag::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $productTag = Tag::find($id);
        if (!$productTag) {
            return $this->errorNotFound();
        }
        return $this->item($productTag, new TagTransformer());
    }

    function delete($id)
    {
        $productTag = Tag::find($id);
        if (!$productTag) {
            return $this->errorNotFound();
        }
        $productTag->delete();
        return $this->noContent();
    }
}
