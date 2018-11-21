<?php

namespace App\Models;

class ProductExaminLog extends Model
{
    protected $table = 'product_examine_log';

    protected static $page_size = 10;

    /**
     * 记录商品审核日志
     * @param $product_id int 商品ID
     * @param $wx_id int 微信公众号ID
     * @param $status int 审核状态
     * @param $reason string 不通过原因
     * @param $op_id int 操作人
     * @return mixed
     */
    static public function insertLog($product_id,$status,$reason,$op_id){
        return self::create([
            'product_id' => $product_id,
            'status' => $status,
            'reason' => $reason,
            'op_id' => $op_id,
        ]);
    }
}
