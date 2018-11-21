<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProductPart;
use App\Transformers\ProductPartTransformer;

class ProductPartController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $productParts = ProductPart::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($productParts, new ProductPartTransformer());
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
        ProductPart::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $productPart = ProductPart::find($id);
        if (!$productPart) {
            return $this->errorNotFound();
        }
        return $this->item($productPart, new ProductPartTransformer());
    }

    function delete($id)
    {
        $productPart = ProductPart::find($id);
        if (!$productPart) {
            return $this->errorNotFound();
        }
        $productPart->delete();
        return $this->noContent();
    }
}
