<?php

namespace App\Models;

class TaskComment extends Model
{
    public $timestamps = false;
    protected $table = 'task_comment';

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
