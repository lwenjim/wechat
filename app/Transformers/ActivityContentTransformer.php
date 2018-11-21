<?php

namespace App\Transformers;

use App\Models\ActivityContent;
use League\Fractal\TransformerAbstract;

class ActivityContentTransformer extends TransformerAbstract
{
    public function transform(ActivityContent $content)
    {
        return $content->attributesToArray();
    }
}
