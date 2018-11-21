<?php

namespace App\Models;

class Spec extends Model
{
    protected $table = 'spec';

    public function values()
    {
        return $this->hasMany(SpecValue::class);
    }
}
