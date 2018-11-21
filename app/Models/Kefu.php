<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Kefu extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['conns'];
    protected $table = 'kefu';

    public function conns()
    {
        return $this->hasMany(KefuConn::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('realname');
    }
}
