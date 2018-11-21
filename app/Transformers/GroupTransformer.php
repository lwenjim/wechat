<?php

namespace App\Transformers;

use App\Models\Group;
use League\Fractal\TransformerAbstract;

class GroupTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['users'];

    public function transform(Group $Group)
    {
        return $Group->attributesToArray();
    }

    public function includeUsers(Group $Group)
    {
        return $this->collection($Group->users()->select('id', 'nickname', 'group_id')->get(), new UserTransformer());
    }
}
