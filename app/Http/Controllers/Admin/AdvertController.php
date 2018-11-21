<?php

namespace App\Http\Controllers\Admin;

use App\Models\Advert;
use App\Transformers\AdvertTransformer;

class AdvertController extends AdminController
{
    function index()
    {
        $where['title'] = $this->request->get('title');
        $where['status'] = $this->request->get('status');
        $where['type'] = $this->request->get('type');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $adverts = Advert::where(function ($query) use ($where) {
            if ($where['title']) {
                $query->where('title', 'like', '%' . $where['title'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            if ($where['type'] != '') {
                $query->where('type', $where['type']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($adverts, new AdvertTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'title' => 'required|max:100',
            'link' => 'required|max:255',
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        Advert::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $advert = Advert::find($id);
        if (!$advert) {
            return $this->errorNotFound();
        }
        return $this->item($advert, new AdvertTransformer());
    }

    function delete($id)
    {
        $advert = Advert::find($id);
        if (!$advert) {
            return $this->errorNotFound();
        }
        $advert->delete();
        return $this->noContent();
    }
}
