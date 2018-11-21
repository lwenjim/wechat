<?php

namespace App\Models;

class Tag extends Model
{
    protected $table = 'tag';

    public function children()
    {
        return $this->hasMany(Tag::class, 'parent_id');
    }

    public function users()
    {
        return $this->morphedByMany(User::class, 'item', 'tag_item');
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'item', 'tag_item');
    }
}
