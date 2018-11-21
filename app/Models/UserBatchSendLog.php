<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/5 0005
 * Time: 19:07
 */

namespace App\Models;


class UserBatchSendLog extends Base
{
    protected $table = 'user_batch_send_log';

    protected $casts = [
        'openids' => 'array',
        'sendResult' => 'array',
    ];
}