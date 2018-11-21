<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = ['created_at', 'updated_at'];
}