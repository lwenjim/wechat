<?php

namespace App\Http\Controllers\Admin;

use App\Models\Catalog;
use App\Transformers\CatalogTransformer;

class CatalogController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('title');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $productCatalogs = Catalog::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            $query->where('parent_id', 0);
        })->orderBy($order_field, $order_type)->get();
        return $this->collection($productCatalogs, new CatalogTransformer());
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
        Catalog::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $productCatalog = Catalog::find($id);
        if (!$productCatalog) {
            return $this->errorNotFound();
        }
        return $this->item($productCatalog, new CatalogTransformer());
    }

    function delete($id)
    {
        $productCatalog = Catalog::find($id);
        if (!$productCatalog) {
            return $this->errorNotFound();
        }
        $productCatalog->delete();
        return $this->noContent();
    }
}
