<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductExaminLog;
use App\Transformers\ProductTransformer;

class ProductExamineController extends Controller
{
    private $wx_id = false;
    public function index(){
        $method = $this->request->get('method','default');
        $this->wx_id = $this->request->get('wx_id',false);
        switch ($method){
            //获取产品列表
            case 'getList':
                return $this->getList();
                break;
            //通过审核
            case 'pass':
                return $this->passItem();
                break;
            //拒绝审核
            case 'refuse':
                return $this->refuseItem();
                break;
            default:
                return $this->errorBadRequest('缺少参数:method');
                break;
        }
    }

    private function getList(){
//        return Product::getUnexamineProducts($this->wx_id);
        $where['title'] = $this->request->get('title');
        $where['status'] = $this->request->get('status');
        $where['stock'] = $this->request->get('stock');
        $where['export'] = $this->request->get('export', 0);
        $where['order'] = $this->request->get('order', 'created_at,desc');
        $wx_id = $this->request->get('wx_id', 0);
        list($order_field, $order_type) = explode(',', $where['order']);
        $products = Product::select('*',
            \DB::raw("(select COUNT(user_stat.id) from user_stat where product.id = user_stat.path_id and user_stat.path = 'shop/product') as pv"),
            \DB::raw("(select COUNT(DISTINCT user_stat.user_id) from user_stat where product.id = user_stat.path_id and user_stat.path = 'shop/product') as uv"),
            \DB::raw("(select SUM(user_order_product.number) from user_order_product where product.id = user_order_product.product_id) as order_count"))->where(function ($query) use ($where,$wx_id) {
            if ($where['title']) {
                $query->where('title', 'like', '%' . $where['title'] . '%')
                    ->orWhere('short_title', 'like', '%' . $where['title'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            if ($where['stock'] != '') {
                $query->whereDoesntHave('specs', function ($query) use ($where) {
                    $query->where('stock', '>', $where['stock']);
                });
            }
            if($wx_id != 0){
                $query->where('wx_id', $wx_id);
            }
        })->withCount(['coupons'])->where('examine',1)->orderBy($order_field, $order_type);
        $list = $products->paginate();
        return $this->paginator($list, new ProductTransformer());
    }
    private function passItem(){
        $product_id = $this->request->get('product_id',false);
        if(!$product_id){
            return $this->errorBadRequest('缺少参数:product_id');
        }
        $res = Product::passItem($product_id);
        if($res){
            ProductExaminLog::insertLog($product_id,3,'',$this->user()->id);
            return $this->created(null,'商品审核状态修改成功');
        }else{
            return $this->errorBadRequest('商品审核状态修改失败');
        }
    }
    private function refuseItem(){
        $product_id = $this->request->get('product_id',false);
        $reason = $this->request->get('reason',false);
        if(!$product_id){
            return $this->errorBadRequest('缺少参数:product_id');
        }
        if(!$reason || (trim($reason) == '')){
            return $this->errorBadRequest('缺少参数:reason');
        }
        $res = Product::refuseItem($product_id,$reason);
        if($res){
            ProductExaminLog::insertLog($product_id,2,$reason,$this->user()->id);
            return $this->created(null,'商品审核状态修改成功');
        }else{
            return $this->errorBadRequest('商品审核状态修改失败');
        }
    }

}