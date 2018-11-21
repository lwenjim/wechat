<?php

namespace App\Transformers;

use App\Models\WeChatStaff;
use League\Fractal\TransformerAbstract;

class WeChatStaffTransformer extends TransformerAbstract
{
    public function transform(WeChatStaff $staff)
    {
        return $staff->attributesToArray();
    }
}
