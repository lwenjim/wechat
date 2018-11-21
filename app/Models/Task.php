<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Task extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['children'];
    protected $table = 'task';

    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')->withPivot('owner', 'remind', 'sort', 'star');
    }

    public function owner()
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')->withPivot('owner', 'remind', 'sort', 'star')->wherePivot('owner', 1);
    }
}
