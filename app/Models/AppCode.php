<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class AppCode extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['users'];
    protected $table = 'appcode';

    public function users()
    {
        return $this->hasMany(AppCodeUser::class, 'appcode_id');
    }
}
