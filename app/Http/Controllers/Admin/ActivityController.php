<?php

namespace App\Http\Controllers\Admin;

use App\Models\Activity;
use App\Transformers\ActivityTransformer;

class ActivityController extends AdminController
{
    function index()
    {
        $where['type'] = $this->request->get('type');
        $where['top'] = $this->request->get('top');
        $where['title'] = $this->request->get('title');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $activitys = Activity::where(function ($query) use ($where) {
            if ($where['title']) {
                $query->where('title', 'like', '%' . $where['title'] . '%');
            }
            if ($where['top'] != '') {
                $query->where('top', $where['top']);
            }
            if ($where['type']) {
                $query->where('type', $where['type']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($activitys, new ActivityTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'title' => 'required|max:255'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $activity = Activity::updateOrCreate(['id' => $id], array_except($data, ['content']));
        if ($id) {
            $activity->content()->update(['content' => $data['content']]);
        } else {
            $activity->content()->create(['content' => $data['content']]);
        }
        return $this->created();
    }

    function get($id)
    {
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->errorNotFound();
        }
        return $this->item($activity, new ActivityTransformer());
    }

    function delete($id)
    {
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->errorNotFound();
        }
        $activity->delete();
        return $this->noContent();
    }
}
