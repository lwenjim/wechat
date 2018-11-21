<?php
/**
 * Created by PhpStorm.
 * User: Mediabook
 * Date: 2018/10/18
 * Time: 16:30
 */

namespace App\Models;


class UserSignComment extends Model
{
    protected $table = 'user_sign_comment';

    public function to_likes()
    {
        return $this->hasMany(UserLike::class, 'to_user_id', 'id');
    }
}