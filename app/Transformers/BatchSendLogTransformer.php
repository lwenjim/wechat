<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/8 0008
 * Time: 14:09
 */

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\UserBatchSendLog;

class BatchSendLogTransformer extends TransformerAbstract
{
    public function transform(UserBatchSendLog $UserBatchSendLog)
    {
        return $UserBatchSendLog->attributesToArray();
    }
}