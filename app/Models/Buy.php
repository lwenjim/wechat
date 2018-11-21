<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Buy extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['content'];
    protected $table = 'buy';
    protected $casts = [
        'images' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function content()
    {
        return $this->hasOne(BuyContent::class);
    }

    public function orders()
    {
        return $this->hasMany(UserBuyOrder::class);
    }
}
