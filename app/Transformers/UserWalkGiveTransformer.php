<?php

namespace App\Transformers;

use App\Models\UserWalkGive;
use League\Fractal\TransformerAbstract;

class UserWalkGiveTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user_walk', 'to_user_walk'];

    public function transform(UserWalkGive $userWalkGive)
    {
        return $userWalkGive->attributesToArray();
    }

    public function includeUserWalk(UserWalkGive $userWalkGive)
    {
        $userWalk = $userWalkGive->user_walk()->select('id', 'user_id', 'date', 'step')->first();
        if ($userWalk) {
            return $this->item($userWalk, new UserWalkTransformer());
        } else {
            return null;
        }
    }

    public function includeToUserWalk(UserWalkGive $userWalkGive)
    {
        $userWalk = $userWalkGive->to_user_walk()->select('id', 'user_id', 'date', 'step')->first();
        if ($userWalk) {
            return $this->item($userWalk, new UserWalkTransformer());
        } else {
            return null;
        }
    }
}
