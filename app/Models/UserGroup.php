<?php

namespace App\Models;

class UserGroup extends Model
{
    protected $table = 'user_group';
    protected $casts = [
        'rule' => 'array'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'user_group_id');
    }
}
