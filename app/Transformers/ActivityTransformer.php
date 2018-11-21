<?php

namespace App\Transformers;

use App\Models\Activity;
use League\Fractal\TransformerAbstract;

class ActivityTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['content'];

    public function transform(Activity $activity)
    {
        return $activity->attributesToArray();
    }

    public function includeContent(Activity $activity)
    {
        return $this->item($activity->content, new ActivityContentTransformer());
    }
}
