<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class KefuConn extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['msgs'];
    protected $table = 'kefu_conn';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function kefu()
    {
        return $this->belongsTo(Kefu::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function msgs()
    {
        return $this->hasMany(KefuConnMsg::class);
    }
}
