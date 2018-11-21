<?php

namespace App\Http\Controllers\Admin;

use App\Models\Spec;
use App\Models\SpecValue;
use App\Transformers\SpecTransformer;

class SpecController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $Specs = Spec::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->withCount(['values' => function ($q) {
            $q->has('product_specs');
        }])->orderBy($order_field, $order_type)->get();
        return $this->collection($Specs, new SpecTransformer());
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
        $spec = Spec::updateOrCreate(['id' => $id], array_except($data, ['id', 'value', 'created_at', 'updated_at']));
        if (isset($data['value']) && is_array($data['value']) && count($data['value']) > 0) {
            foreach ($data['value'] as $val) {
                $spec->values()->updateOrCreate(['id' => $val['id']], array_except($val, ['id', 'created_at', 'updated_at']));
            }
        }
        return $this->created();
    }

    function get($id)
    {
        $Spec = Spec::find($id);
        if (!$Spec) {
            return $this->errorNotFound();
        }
        return $this->item($Spec, new SpecTransformer());
    }

    function delete($id)
    {
        $Spec = Spec::find($id);
        if (!$Spec) {
            return $this->errorNotFound();
        }
        $Spec->delete();
        return $this->noContent();
    }

    function deleteValue($value_id)
    {
        $SpecValue = SpecValue::find($value_id);
        if (!$SpecValue) {
            return $this->errorNotFound();
        }
        $SpecValue->delete();
        return $this->noContent();
    }
}
