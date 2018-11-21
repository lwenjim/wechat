<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/10 0010
 * Time: 22:55:06
 */

namespace App\Models;


class UserOpenids extends Model
{
    protected $table = 'user_openids';

    function user()
    {
        return $this->belongsTo(User::class);
    }
}