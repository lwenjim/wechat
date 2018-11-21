<?php

namespace App\Transformers;

use App\Models\Express;
use League\Fractal\TransformerAbstract;

class ExpressTransformer extends TransformerAbstract
{
    public function transform(Express $Express)
    {
        return $Express->attributesToArray();
    }
}
