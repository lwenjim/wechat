<?php

namespace App\Transformers;

use App\Models\UserStaffSendLog;
use League\Fractal\TransformerAbstract;

class UserStaffSendLogTransformer extends TransformerAbstract
{
    public function transform(UserStaffSendLog $UserStaffSendLog)
    {
        return $UserStaffSendLog->attributesToArray();
    }
}
