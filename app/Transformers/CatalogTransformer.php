<?php

namespace App\Transformers;

use App\Models\Catalog;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class CatalogTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['children', 'products'];

    public function transform(Catalog $productCatalog)
    {
        return $productCatalog->attributesToArray();
    }

    public function includeChildren(Catalog $productCatalog)
    {
        return $this->collection($productCatalog->children()->orderBy('sort', 'asc')->get(), new CatalogTransformer());
    }

    public function includeProducts(Catalog $productCatalog, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        if ($params->get('order')) {
            list($orderBy, $orderType) = $params->get('order');
        } else {
            list($orderBy, $orderType) = ['sort', 'asc'];
        }
        if ($params->get('partag')) {
            list($part, $tag) = $params->get('partag');
        } else {
            list($part, $tag) = [0, 0];
        }
        $items = $productCatalog->products()->select('id', 'title', 'short_title', 'image', 'order', 'link',
            \DB::raw("(select SUM(user_order_product.number) from user_order_product where product.id = user_order_product.product_id) as order_count"),
            \DB::raw("(select MIN(product_spec.price) from product_spec where product.id = product_spec.product_id) as product_price"
            ))->where('status', 1)->when($tag, function ($query) use ($tag) {
            return $query->whereHas('tags', function ($query) use ($tag) {
                $query->where('id', $tag);
            });
        })->withCount(['coupons'])->orderBy($orderBy, $orderType)->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        return $this->collection($items, new ProductTransformer());
    }
}
