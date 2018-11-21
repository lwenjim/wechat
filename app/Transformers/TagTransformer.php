<?php

namespace App\Transformers;

use App\Models\Tag;
use League\Fractal\TransformerAbstract;

class TagTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['children', 'users', 'products'];

    public function transform(Tag $userTag)
    {
        return $userTag->attributesToArray();
    }

    public function includeChildren(Tag $userTag)
    {
        return $this->collection($userTag->children()->get(), new TagTransformer());
    }

    public function includeUsers(Tag $userTag)
    {
        return $this->collection($userTag->users()->select('id', 'nickname', 'headimgurl')->get(), new UserTransformer());
    }

    public function includeProducts(Tag $userTag)
    {
        return $this->collection($userTag->products()->select('id', 'short_title', 'product_part_id')->get(), new ProductTransformer());
    }
}
