<?php

namespace App\Http\Controllers\Admin;

use App\Models\Express;
use App\Transformers\ExpressTransformer;

class ExpressController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $product_expresss = Express::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($product_expresss, new ExpressTransformer());
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
        Express::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $advertCatalog = Express::find($id);
        if (!$advertCatalog) {
            return $this->errorNotFound();
        }
        return $this->item($advertCatalog, new ExpressTransformer());
    }

    function delete($id)
    {
        $advertCatalog = Express::find($id);
        if (!$advertCatalog) {
            return $this->errorNotFound();
        }
        $advertCatalog->delete();
        return $this->noContent();
    }
}
