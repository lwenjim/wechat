<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/19 0019
 * Time: 14:58
 */

namespace App\Models;
class WXMaterialNewsSendedHistory extends Model
{
    protected $table = 'wxmaterial_news_sended_history';
    protected $casts = [
        'content' => 'array'
    ];
}