<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\ProductComment;
use App\Transformers\ProductTransformer;
use App\Transformers\ProductCommentTransformer;

class ProductController extends AdminController
{
    function index($wx_id = 0)
    {
        $where['title'] = $this->request->get('title');
        $where['status'] = $this->request->get('status');
        $where['stock'] = $this->request->get('stock');
        $where['export'] = $this->request->get('export', 0);
        $where['order'] = $this->request->get('order', 'created_at,desc');
        $for_sale = $this->request->get('for_sale', 0);
        $can_quote = $this->request->get('can_quote', 0);
        list($order_field, $order_type) = explode(',', $where['order']);
        $products = Product::select('*',
            \DB::raw("(select COUNT(user_stat.id) from user_stat where product.id = user_stat.path_id and user_stat.path = 'shop/product') as pv"),
            \DB::raw("(select COUNT(DISTINCT user_stat.user_id) from user_stat where product.id = user_stat.path_id and user_stat.path = 'shop/product') as uv"),
            \DB::raw("(select SUM(user_order_product.number) from user_order_product where product.id = user_order_product.product_id) as order_count")
        )->where(function ($query) use ($where,$wx_id,$for_sale,$can_quote) {
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
            if($for_sale != 0){
                $query->where('for_sale', $for_sale);
            }
            if($can_quote != 0){
                $query->where('can_quote', $can_quote);
            }
        })->withCount(['coupons'])->orderBy($order_field, $order_type);
        if ($where['export']) {
            $list = $products->get();
            $data = [];
            $status = ['已下架', '已上架'];
            foreach ($list as $k => $v) {
                $data[$k]['编号'] = $v->id;
                $data[$k]['标题'] = $v->title;
                $data[$k]['分类'] = $v->catalog->title;
                $data[$k]['状态'] = $status[$v->status];
                $data[$k]['库存'] = $v->specs()->get()->sum(function ($item) {
                    return $item->stock;
                });
                $data[$k]['销量'] = $v->user_order_products_count;
                $data[$k]['PV'] = $v->pv;
                $data[$k]['UV'] = $v->uv;
                $data[$k]['排序'] = $v->sort;
            }
            app('excel')->create(date('Y-m-d H:i:s') . '魔都巴士商品列表', function ($excel) use ($data) {
                $excel->sheet('魔都巴士商品列表', function ($sheet) use ($data) {
                    $sheet->fromArray($data, 'null', 'A1', true, true);
                });
            })->export('xlsx');
        } else {
            $list = $products->paginate();
            $list = $this->dealQuoteProduct($list);//处理引用商品
        }
        return $this->paginator($list, new ProductTransformer());
    }

    //处理引用商品
    function dealQuoteProduct($list){
        $quote_list = [];
        foreach ($list as $k => $v){
            if($v['quote_id'] != 0){
                array_push($quote_list,$v['quote_id']);
            }
        }
        if(!empty($quote_list)){
            $res = Product::getProducts($quote_list);
            foreach ($list as $k => $v){
                foreach ($res as $kk => $vv) {
                    if ($v['quote_id'] == $vv['id']) {
                        $list[$k]['quote_info'] = $vv;
                    }
                }
            }
        }
        return $list;
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'title' => 'required|max:255',
            'short_title' => 'required|max:255',
            'content' => 'required',
            'wx_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $product = Product::updateOrCreate(['id' => $id], array_except($data, ['spec', 'catalogs', 'tags', 'content','api_token']));
        if ($product) {
            if (is_array($data['spec']) && count($data['spec']) > 0) {
                foreach ($data['spec'] as $v) {
                    $productSpec = $product->specs()->updateOrCreate(['id' => $v['id']], array_except($v, [
                        'id', 'group', 'spec_value', 'created_at', 'updated_at', 'user_order_products_count','user_group'
                    ]));
                    if (isset($v['user_group']) && is_array($v['user_group']) && count($v['user_group']) > 0) {
                        foreach ($v['user_group'] as $val) {
                            $productSpec->prices()->updateOrCreate(['id' => $val['id']], array_except($val, [
                                'id', 'created_at', 'updated_at'
                            ]));
                        }
                    }
                    if (isset($v['spec_value']) && count($v['spec_value']) > 0) {
                        $productSpec->spec_values()->sync($v['spec_value']);
                    }
                }
            }
            $product->catalogs()->sync(explode(',', $data['catalogs']));
            if (isset($data['tags'])) {
                $product->tags()->sync(explode(',', $data['tags']));
            }
            if ($id) {
                $product->content()->update(['content' => $data['content']]);
            } else {
                $product->content()->create(['content' => $data['content']]);
            }
        }
        return $product->id;
    }

    function get($id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->errorNotFound();
        }
        $product = $this->dealQuoteProduct([$product]);
        return $this->item($product[0], new ProductTransformer());
    }

    function delete($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->errorNotFound();
        }
        $product->delete();
        return $this->noContent();
    }

    function deleteSpec($spec_id)
    {
        $productSpec = ProductSpec::find($spec_id);
        if (!$productSpec) {
            return $this->errorNotFound();
        }
        $productSpec->delete();
        return $this->noContent();
    }

    function comment($id)
    {
        $comment = ProductComment::where('product_id', $id)->orderBy('created_at', 'desc')->paginate();
        return $this->paginator($comment, new ProductCommentTransformer());
    }

    function postComment($id, $spec_id)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'user_name' => 'required',
            'user_image' => 'required',
            'score' => 'required',
            'content' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        $data['product_id'] = $id;
        $data['product_spec_id'] = $spec_id;
        ProductComment::create($data);
        return $this->created();
    }

    function putComment($comment_id)
    {
        $productComment = ProductComment::find($comment_id);
        if (!$productComment) {
            return $this->errorNotFound();
        }
        $data = $this->request->input();
        if ($productComment->update($data)) {
            return $this->created();
        } else {
            return $this->noContent();
        }
    }

    function deleteComment($comment_id)
    {
        $productComment = ProductComment::find($comment_id);
        if (!$productComment) {
            return $this->errorNotFound();
        }
        $productComment->delete();
        return $this->noContent();
    }
}
