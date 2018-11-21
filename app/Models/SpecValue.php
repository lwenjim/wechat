<?php

namespace App\Models;

class SpecValue extends Model
{
    protected $table = 'spec_value';

    public function spec()
    {
        return $this->belongsTo(Spec::class);
    }

    public function product_specs()
    {
        return $this->belongsToMany(ProductSpec::class, 'spec_value_product_spec', 'spec_value_id', 'product_spec_id');
    }
}
