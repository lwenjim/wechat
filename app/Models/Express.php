<?php

namespace App\Models;

class Express extends Model
{
    protected $table = 'express';
    protected $casts = [
        'content' => 'array'
    ];
}
