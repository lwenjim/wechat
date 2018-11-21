<?php

namespace App\Transformers;

use App\Models\Achievement;
use League\Fractal\TransformerAbstract;

class AchievementTransformer extends TransformerAbstract
{
    public function transform(Achievement $achievement)
    {
        return $achievement->attributesToArray();
    }
}
