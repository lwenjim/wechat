<?php

namespace App\Transformers;

use App\Models\TaskComment;
use League\Fractal\TransformerAbstract;

class TaskCommentTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'task'];

    public function transform(TaskComment $Comment)
    {
        return $Comment->attributesToArray();
    }

    public function includeUser(TaskComment $Comment)
    {
        $user = $Comment->user()->select('id', 'openid', 'nickname', 'headimgurl')->first();
        if ($user) {
            return $this->item($user, new UserTransformer());
        }
    }

    public function includeTask(TaskComment $Comment)
    {
        $task = $Comment->task()->select('id', 'name')->first();
        if ($task) {
            return $this->item($task, new TaskTransformer());
        }
    }
}
