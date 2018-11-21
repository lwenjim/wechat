<?php

namespace App\Transformers;

use App\Models\UserFeedback;
use League\Fractal\TransformerAbstract;

class UserFeedbackTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(UserFeedback $userFeedback)
    {
        return $userFeedback->attributesToArray();
    }

    public function includeUser(UserFeedback $userFeedback)
    {
        return $this->item($userFeedback->user()->select('id', 'nickname', 'headimgurl')->first(), new UserTransformer());
    }
}
