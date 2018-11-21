<?php

namespace App\Models;

use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserOrder extends Model
{
    use SoftDeletes, SoftCascadeTrait;
    protected $softCascade = ['products', 'comments', 'afters', 'logs'];
    protected $table = 'user_order';
    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function express()
    {
        return $this->belongsTo(Express::class, 'express_id');
    }

    public function products()
    {
        return $this->hasMany(UserOrderProduct::class);
    }

    public function comments()
    {
        return $this->hasMany(UserOrderComment::class);
    }

    public function afters()
    {
        return $this->hasMany(UserOrderAfter::class);
    }

    public function logs()
    {
        return $this->hasMany(UserOrderLog::class);
    }
}