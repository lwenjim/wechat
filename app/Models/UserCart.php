<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserCart extends Model
{
    use SoftDeletes;
    protected $table = 'user_cart';
    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_spec()
    {
        return $this->belongsTo(ProductSpec::class);
    }
}
