<?php

namespace App\Transformers;

use App\Models\WeChatAchieve;
use League\Fractal\TransformerAbstract;

class WeChatAchieveTransformer extends TransformerAbstract
{
    public function transform(WeChatAchieve $WeChatAchieve)
    {
        return $WeChatAchieve->attributesToArray();
    }
}
