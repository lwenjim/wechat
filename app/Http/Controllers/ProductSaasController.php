<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthToken;
use App\Models\Product;
use App\Models\ProductExaminLog;
use Illuminate\Support\Facades\Auth;

class ProductSaasController extends Controller
{
    private $wx_id = false;
    public function index(){
        $method = $this->request->get('method','default');
        //获取单个产品
        if($method == 'getOne'){
            return $this->getOne();
        }
        $this->wx_id = $this->request->get('wx_id',false);
        if(!$this->wx_id){
            return $this->errorBadRequest('缺少参数:wx_id');
        }
        switch ($method){
//            //前台获取产品列表
//            case 'getList':
//                return $this->getList();
//                break;
//            //后台获取产品列表
//            case 'getAdminList':
//                return $this->getAdminList();
//                break;
//            //添加和编辑
//            case 'saveData':
//                return $this->saveData();
//                break;
            //提交审核
            case 'examine':
                return $this->examine();
                break;
            //商品上线
            case 'online':
                return $this->changeLine(1);
                break;
            //商品下线
            case 'offline':
                return $this->changeLine(0);
                break;
            //商品删除
            case 'delete':
                return $this->deleteProduct();
                break;
            //引用商品
            case 'quote':
                return $this->quoteProduct();
                break;
            //开启引用
            case 'quoteon':
                return $this->changeQuote(1);
                break;
            //开启引用
            case 'quoteoff':
                return $this->changeQuote(0);
                break;
            default:
                return $this->errorBadRequest('缺少参数:method');
                break;
        }
    }

    private function getOne(){
        $product_id = $this->request->get('product_id',false);
        if(!$product_id){
            return $this->errorBadRequest('缺少参数:product_id');
        }
        return Product::find($product_id);
    }

    private function getList(){
        $list = Product::getSaasProductByWxid($this->wx_id);
        $quote_list = [];
        $list3 = $list->toArray();
        $list2 = $list3['data'];
        array_walk($list2,function($v)use(&$quote_list){
            if($v['quote_id'] != 0){
                $quote_list[] = $v['quote_id'];
            }
        });
        $quote_list = Product::getProducts($quote_list);
        //db里没找到能代替column的方法.只能手写了..
        $quote_list2 = [];
        array_walk($quote_list,function($v)use(&$quote_list2){
            $quote_list2[$v['id']] = $v;
        });
        //这里是直接用引用过来的信息替换了原来的
        array_walk($list2,function(&$v)use(&$quote_list2){
            if($v['quote_id'] != 0){
                $v = $quote_list2[$v['quote_id']];
            }
        });
        $list3['data'] = $list2;
        return $list3;
    }

    private function getAdminList(){
        $list = Product::getSaasProductByWxid($this->wx_id);
        $list = $list->toArray();
        return $list;
    }

    //保存数据
    private function saveData(){
        $request = $this->request->input();
        $data = isset($request['data']) ? json_decode($request['data'],true) : [];
        $validator = \Validator::make($data, [
            'title' => 'required|max:255',
            'short_title' => 'required|max:255',
            'content' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $id = isset($data['id']) ? $data['id'] : 0 ;
        if($id != 0){
            $product = $this->checkProduct($id,['id','status','for_sale','examine']);
            if($product['for_sale'] == 1){
                return $this->errorBadRequest('商品必须下架才能保存数据');
            }
        }
        $data['wx_id'] = $this->wx_id;
        $data['examine'] = 0;//重置审核状态
        $product = Product::saveData($data,$id);
        if($product->id){
            return $this->created(null,'保存商品成功');
        }else{
            return $this->errorBadRequest('保存商品失败');
        }
    }

    //商品审核
    private function examine(){
        $product_id = $this->checkProduct();
        if(Product::examineProduct($product_id)){
            ProductExaminLog::insertLog($product_id,1,'',$this->user()->id);
            return $this->created(null,'商品审核中');
        }else{
            return $this->errorBadRequest('请求商品审核失败');
        }
    }

    //删除商品
    private function deleteProduct(){
        $product_id = $this->checkProduct();
        if(Product::deleteProduct($product_id)){
            return $this->created(null,'删除商品成功');
        }else{
            return $this->errorBadRequest('删除商品失败');
        }
    }

    //商品上下架
    private function changeLine($status){
        $product_id = $this->checkProduct(false,['id','examine']);
        if($product_id['examine'] != 3){
            return $this->errorBadRequest('该商品未通过审核,无法上下架');
        }
        if(Product::changeLine($product_id['id'],$status)){
            return $this->created(null,'商品上下架成功');
        }else{
            return $this->errorBadRequest('商品上下架失败');
        }
    }

    //引用商品
    private function quoteProduct(){
        $quote_id = $this->request->get('quote_id',false);
        if(!$quote_id){
            return $this->errorBadRequest('缺少参数:quote_id');
        }
        if($this->canQuote($quote_id)){
            $res = Product::quoteProduct($this->wx_id,$quote_id);
            if($res->id){
                return $this->created(null,'引用商品成功');
            }else{
                return $this->errorBadRequest('引用商品失败');
            }
        }
    }

    //检查 公众号ID 和 商品ID 是否匹配
    private function checkProduct($id = false,$field = false){
        $product_id = $id;
        if(!$product_id){
            $product_id = $this->request->get('product_id',false);
        }
        if(!$product_id){
            return $this->errorBadRequest('缺少参数:product_id');
        }
        $res = Product::checkWxidAndProductid($this->wx_id,$product_id,$field);
        if(!$res){
            return $this->errorBadRequest('商品ID出错');
        }
        if($field){
            return $res;
        }else{
            return $product_id;
        }
    }

    //检查 商品ID 能否被引用
    private function canQuote($id){
        $res = Product::getOneProduct($id,['can_quote']);
        if($res && $res['can_quote'] == 1){
            return true;
        }else{
            return $this->errorBadRequest('该商品无法被引用');
        }
    }

    //改变引用状态
    private function changeQuote($status){
        $product_id = $this->request->get('product_id',false);
        if(!$product_id){
            return $this->errorBadRequest('缺少参数:product_id');
        }
        $res = Product::changeQuote($product_id,$status);
        if($res){
            return $this->created(null,'修改商品引用状态成功');
        }else{
            return $this->errorBadRequest('修改商品引用状态失败');
        }
    }
}