<?php

namespace App\Transformers;

use App\Models\Product;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['content', 'spec', 'specs', 'comment', 'comments', 'catalogs', 'coupons', 'tags'];

    public function transform(Product $product)
    {
        return $product->attributesToArray();
    }

    public function includeContent(Product $product)
    {
        return $this->item($product->content, new ProductContentTransformer());
    }

    public function includeSpec(Product $product)
    {
        return $this->collection($product->specs()->get(), new ProductSpecTransformer());
    }

    public function includeSpecs(Product $product, ParamBag $params = null)
    {
        return $this->collection($product->specs()->select('id', 'product_id', 'image', 'day', 'limit', 'price', 'market_price', 'stock', 'pay')->where('status', 1)->withCount(['likes' => function ($query) {
            $query->where('user_id', \Auth::check() ? \Auth::user()->id : 0);
        }])->orderBy('sort', 'asc')->get(), new ProductSpecTransformer());
    }

    public function includeComment(Product $product)
    {
        return $this->collection($product->comments()->get(), new ProductCommentTransformer());
    }

    public function includeComments(Product $product)
    {
        return $this->collection($product->comments()->select('id', 'product_id', 'product_spec_id', 'user_id', 'user_name', 'user_image', 'score', 'images', 'content', 'created_at')->where('status', 1)->orderBy('sort', 'asc')->get(), new ProductCommentTransformer());
    }

    public function includeCatalogs(Product $product)
    {
        return $this->collection($product->catalogs()->select('id', 'name', 'image')->get(), new CatalogTransformer());
    }

    public function includeCoupons(Product $product)
    {
        return $this->collection($product->coupons()->select('id', 'name', 'money')->where('status', 1)->get(), new CouponTransformer());
    }

    public function includeTags(Product $product)
    {
        return $this->collection($product->tags()->select('id', 'name', 'image', 'active')->where('status', 1)->orderBy('sort', 'asc')->get(), new TagTransformer());
    }
}
