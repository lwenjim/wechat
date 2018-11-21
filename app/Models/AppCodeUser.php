<?php

namespace App\Models;

class AppCodeUser extends Model
{
    public $timestamps = false;
    protected $table = 'appcode_user';

    public function appcode()
    {
        return $this->belongsTo(AppCode::class, 'appcode_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
