<?php

namespace App\Models;

class Group extends Model
{
    protected $table = 'group';
    protected $casts = [
        'rule' => 'array'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
