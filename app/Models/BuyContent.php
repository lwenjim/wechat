<?php

namespace App\Models;

class BuyContent extends Model
{
    public $primaryKey = 'buy_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'buy_content';

    public function buy()
    {
        return $this->belongsTo(Buy::class);
    }
}
