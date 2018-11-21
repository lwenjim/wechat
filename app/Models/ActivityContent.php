<?php

namespace App\Models;

class ActivityContent extends Model
{
    public $primaryKey = 'activity_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'activity_content';

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
