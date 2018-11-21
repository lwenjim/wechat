<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProductExpress;
use App\Transformers\ProductExpressTransformer;

class ProductExpressController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $product_expresss = ProductExpress::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($product_expresss, new ProductExpressTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:255',
            'content' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        ProductExpress::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $advertCatalog = ProductExpress::find($id);
        if (!$advertCatalog) {
            return $this->errorNotFound();
        }
        return $this->item($advertCatalog, new ProductExpressTransformer());
    }

    function delete($id)
    {
        $advertCatalog = ProductExpress::find($id);
        if (!$advertCatalog) {
            return $this->errorNotFound();
        }
        $advertCatalog->delete();
        return $this->noContent();
    }
}
