<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Catalog extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['children', 'products'];
    protected $table = 'catalog';

    public function children()
    {
        return $this->hasMany(Catalog::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
