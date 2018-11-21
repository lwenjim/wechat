<?php

namespace App\Transformers;

use App\Models\BuyContent;
use League\Fractal\TransformerAbstract;

class BuyContentTransformer extends TransformerAbstract
{
    public function transform(BuyContent $content)
    {
        return $content->attributesToArray();
    }
}
