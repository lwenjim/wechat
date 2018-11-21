<?php

namespace App\Transformers;

use App\Models\Spec;
use League\Fractal\TransformerAbstract;

class SpecTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['values'];

    public function transform(Spec $Spec)
    {
        return $Spec->attributesToArray();
    }

    public function includeValues(Spec $Spec)
    {
        return $this->collection($Spec->values()->orderBy('sort', 'asc')->get(), new SpecValueTransformer());
    }
}
