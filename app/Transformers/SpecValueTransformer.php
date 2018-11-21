<?php

namespace App\Transformers;

use App\Models\SpecValue;
use League\Fractal\TransformerAbstract;

class SpecValueTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['spec', 'product_specs'];

    public function transform(SpecValue $SpecValue)
    {
        return $SpecValue->attributesToArray();
    }

    public function includeSpec(SpecValue $SpecValue)
    {
        $spec = $SpecValue->spec()->first();
        if ($spec) {
            return $this->item($spec, new SpecTransformer());
        }
    }

    public function includeProductSpecs(SpecValue $SpecValue)
    {
        return $this->collection($SpecValue->product_specs()->orderBy('sort', 'asc')->get(), new ProductSpecTransformer());
    }
}
