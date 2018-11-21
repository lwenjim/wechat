<?php

namespace App\Models;
use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class MsgSession extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['msgs'];
    protected $table = 'msg_session';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function msgs(){
        return $this->hasMany(Msg::class,'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
