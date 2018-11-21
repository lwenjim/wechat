<?php

namespace App\Models;

class UserStat extends Model
{
    public $timestamps = false;
    protected $table = 'user_stat';
    protected $casts = [
        'query' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
