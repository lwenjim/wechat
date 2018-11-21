<?php

namespace App\Transformers;

use App\Models\UserGroup;
use League\Fractal\TransformerAbstract;

class UserGroupTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['users'];

    public function transform(UserGroup $UserGroup)
    {
        return $UserGroup->attributesToArray();
    }

    public function includeUsers(UserGroup $UserGroup)
    {
        return $this->collection($UserGroup->users()->select('id', 'nickname', 'user_group_id')->get(), new UserTransformer());
    }
}
