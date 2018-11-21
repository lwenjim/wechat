<?php

namespace App\Models;

use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;

class Product extends Model
{
    use CascadesDeletes;
    protected $cascadeDeletes = ['specs', 'comments'];
    protected $table = 'product';
    protected $casts = [
        'images' => 'array'
    ];
    protected static $page_size = 10;

    public function content()
    {
        return $this->hasOne(ProductContent::class);
    }

    public function comment()
    {
        return $this->hasMany(ProductComment::class);
    }

    public function comments()
    {
        return $this->hasMany(ProductComment::class);
    }

    public function spec()
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function specs()
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function user_order_products()
    {
        return $this->hasMany(UserOrderProduct::class);
    }

    public function catalogs()
    {
        return $this->belongsToMany(Catalog::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'item', 'tag_item');
    }

    //获取单个商品信息
    static public function getOneProduct($id,$field = false){
        $res = self::where('id',$id);
        if($field){
            $res = $res->select($field);
        }
        $res = $res->first();
        if ($res){
            $res = $res->toArray();
        }
        return $res;
    }
    //根据 商品ID 获取多个商品
    static public function getProducts($ids,$field = false){
        $res = self::whereIn('id',$ids);
        if($field){
            $res = $res->select($field);
        }
        $res = $res->get();
        if ($res){
            $res = $res->toArray();
        }
        return $res;
    }
    //添加或编辑
    static public function saveData($data,$id = 0){
        return self::updateOrCreate(['id'=>$id],$data);
    }
    //通过 公众号ID ,获取旗下商品
    static public function getSaasProductByWxid($wx_id){
        return self::where('wx_id',$wx_id)->paginate(self::$page_size);
    }
    //检查 公众号ID 和 商品ID 是否匹配
    static public function checkWxidAndProductid($wx_id,$product_id,$field = false){
        $res = self::where(['wx_id'=>$wx_id,'id'=>$product_id]);
        if($field){
            $res = $res->select($field);
        }
        $res = $res->first();
        if ($res){
            $res = $res->toArray();
        }
        return $res;
    }
    //删除商品
    static public function deleteProduct($product_id){
        return self::where('id',$product_id)->update(['status'=>0]);
    }
    //商品上下架
    static public function changeLine($product_id,$l){
        return self::where('id',$product_id)->update(['for_sale'=>$l]);
    }
    //提交审核
    static public function examineProduct($product_id){
        return self::where('id',$product_id)->update(['examine'=>1]);
    }
    //引用商品
    static public function quoteProduct($wx_id,$id){
        return self::firstOrCreate(['quote_id'=>$id,'wx_id'=>$wx_id,'examine'=>3]);
    }
    //改变商品是否可以被引用的状态
    static public function changeQuote($product_id,$status){
        return self::where('id',$product_id)->update(['can_quote'=>$status]);
    }

    //获取未审核商品
    static public function getUnexamineProducts($wx_id = false){
        $res = self::where('examine',1);
        if($wx_id){
            return $res = $res->where('wx_id',$wx_id)->paginate(self::$page_size)->toArray();
        }else{
            return $res = $res->paginate(self::$page_size)->toArray();
        }
    }
    //审核拒绝
    static public function refuseItem($product_id,$reason){
        return self::where('id',$product_id)->update(['reason'=>$reason,'examine'=>2]);
    }
    //审核通过
    static public function passItem($product_id){
        return self::where('id',$product_id)->update(['examine'=>3]);
    }

}
