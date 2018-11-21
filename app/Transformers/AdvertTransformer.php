<?php

namespace App\Transformers;

use App\Models\Advert;
use League\Fractal\TransformerAbstract;

class AdvertTransformer extends TransformerAbstract
{
    public function transform(Advert $advert)
    {
        return $advert->attributesToArray();
    }
}
