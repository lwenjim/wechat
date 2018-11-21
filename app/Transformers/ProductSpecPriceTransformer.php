<?php

namespace App\Transformers;

use App\Models\ProductSpecPrice;
use League\Fractal\TransformerAbstract;

class ProductSpecPriceTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['group', 'product_spec'];

    public function transform(ProductSpecPrice $SpecPrice)
    {
        return $SpecPrice->attributesToArray();
    }

    public function includeGroup(ProductSpecPrice $SpecPrice)
    {
        $Group = $SpecPrice->group()->select('id', 'name', 'image')->first();
        if ($Group) {
            return $this->item($Group, new GroupTransformer());
        }
    }

    public function includeProductSpec(ProductSpecPrice $SpecPrice)
    {
        $productSpec = $SpecPrice->product_spec()->first();
        if ($productSpec) {
            return $this->item($productSpec, new ProductSpecTransformer());
        }
    }
}
