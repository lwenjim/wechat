<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserOrderLog extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'user_order_log';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_order()
    {
        return $this->belongsTo(UserOrder::class, 'user_order_id');
    }
}
