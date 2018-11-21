<?php

namespace App\Transformers;

use App\Models\Task;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class TaskTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['children', 'comments', 'users'];

    public function transform(Task $Task)
    {
        return $Task->attributesToArray();
    }

    public function includeChildren(Task $Task)
    {
        return $this->collection($Task->children()->orderBy('id', 'desc')->get(), new TaskTransformer());
    }

    public function includeComments(Task $Task, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $comments = $Task->comments()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $Task->comments()->count();
        return $this->collection($comments, new TaskCommentTransformer())->setMeta(['total' => $total]);
    }

    public function includeUsers(Task $Task)
    {
        return $this->collection($Task->users()->select('id', 'nickname', 'headimgurl')->get()->map(function ($item) {
            $item->pivot = ['owner' => $item->pivot->owner, 'remind' => json_decode($item->pivot->remind, true), 'sort' => $item->pivot->sort, 'star' => $item->pivot->star];
            return $item;
        }), new UserTransformer());
    }
}
