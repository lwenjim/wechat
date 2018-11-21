<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Advert;
use App\Models\Coupon;
use App\Models\CouponItem;
use App\Models\Product;
use App\Models\Catalog;
use App\Models\Express;
use App\Models\ProductSpec;
use App\Models\UserAddress;
use App\Models\UserCart;
use App\Models\UserOrder;
use App\Models\UserOrderProduct;
use App\Transformers\ProductTransformer;
use App\Transformers\CatalogTransformer;
use App\Transformers\TagTransformer;
use DB;

class ShopController extends Controller
{
    //首页页面推荐商品
    public function productTitles()
    {
        $user = $this->user()->switchToMiniUser();
        $products_down = DB::select('select distinct(product.id), product.title, product.short_title, product.image, product.created_at, product_spec.price, product_spec.blue_diamond from product, product_spec where product.status=1 and product_spec.product_id=product.id and product_spec.blue_diamond < ' . $user->blue_diamond . ' ORDER BY blue_diamond DESC limit 1');
        if (empty($products_down)) {
            $limit = 3;
            $products_down = [];
        } else {
            $limit = 2;
        }
        $products_up = DB::select('select distinct(product.id), product.title, product.short_title, product.image, product.created_at, product_spec.price, product_spec.blue_diamond from product, product_spec where product.status=1 and product_spec.product_id=product.id and product_spec.blue_diamond >= ' . $user->blue_diamond . ' ORDER BY blue_diamond ASC limit ' . $limit);
        return array_merge($products_down, $products_up);
    }

    public function advert()
    {
        $advert = Advert::select('id', 'title', 'link', 'image')->where(['status' => 1, 'type' => 'shop'])->orderBy('sort', 'asc')->get();
        return $advert;
    }

    public function search()
    {
        $keyword = $this->request->get('keyword');
        $product = Product::select('id', 'title', 'short_title', 'image')->withCount('comments')->where('status', 1)->where('title', 'like', "%$keyword%")->orWhere('title', 'like', "%$keyword%")->paginate();
        return $this->paginator($product, new ProductTransformer());
    }

    public function tag($id)
    {
        $tag = Tag::select('id', 'name', 'image')->where(['status' => 1, 'id' => $id])->firstOrFail();
        return $this->item($tag, new TagTransformer());
    }

    public function catalog($id = 0)
    {
        $catalogs = Catalog::select('id', 'parent_id', 'name', 'image')->where(function ($query) use ($id) {
            if ($id) {
                $query->where('id', $id);
            } else {
                $query->where('parent_id', 0);
            }
            $query->where('status', 1);
        })->orderBy('sort', 'asc')->get();
        return $this->collection($catalogs, new CatalogTransformer());
    }

    public function product($id)
    {
        $product = Product::select('id', 'title', 'short_title', 'image', 'images', 'order')->withCount('comments')->where(['id' => $id, 'status' => 1])->firstOrFail();
        return $this->item($product, new ProductTransformer());
    }

    public function like($id)
    {
        $user_id = $this->user()->id;
        $productSpec = ProductSpec::select('id', 'status')->where('status', 1)->findOrFail($id);
        if ($this->request->isMethod('put')) {
            if ($productSpec->likes()->where('user_id', $user_id)->count() == 1) {
                $productSpec->likes()->where('user_id', $user_id)->delete();
            }
        }
        if ($this->request->isMethod('get')) {
            if ($productSpec->likes()->where('user_id', $user_id)->count() == 0) {
                $productSpec->likes()->create(['user_id' => $user_id]);
            }
        }
        return $this->created();
    }

    public function cart()
    {
        if ($this->request->isMethod('post')) {
            $data = $this->request->input();
            $validator = \Validator::make($data, [
                'product_id' => 'required|integer',
                'product_spec_id' => 'required|integer',
                'number' => 'required|integer'
            ]);
            if ($validator->fails()) {
                return $this->errorBadRequest($validator->messages());
            }
            $stock = ProductSpec::where(['status' => 1, 'product_id' => $data['product_id'], 'id' => $data['product_spec_id']])->value('stock');
            $cart = UserCart::where(['user_id' => $this->user()->id, 'product_id' => $data['product_id'], 'product_spec_id' => $data['product_spec_id']])->first();
            if ($data['number'] > 0) {
                if ($cart) {
                    $number = $cart->number;
                } else {
                    $number = 0;
                }
                if ($data['number'] + $number <= $stock) {
                    if ($cart) {
                        $cart->increment('number', $data['number']);
                    } else {
                        $cart = UserCart::create(['user_id' => $this->user()->id, 'product_id' => $data['product_id'], 'product_spec_id' => $data['product_spec_id'], 'number' => $data['number']]);
                    }
                } else {
                    return $this->errorBadRequest('库存不足！');
                }
            } else if ($data['number'] < 0) {
                if ($cart) {
                    if ($data['number'] + $cart->number <= 0) {
                        $cart->delete();
                    } else {
                        $cart->decrement('number', abs($data['number']));
                    }
                } else {
                    return $this->errorBadRequest('商品不存在！');
                }
            } else {
                return $this->errorBadRequest('数量不能为0！');
            }
            return $cart;
        } else {
            return UserCart::select('id', 'product_id', 'product_spec_id', 'number')->where('user_id', $this->user()->id)->with(['product' => function ($query) {
                $query->select('id', 'title', 'short_title', 'image');
            }, 'product_spec' => function ($query) {
                $query->select('id', 'image', 'price', 'weight', 'stock', 'pay')->with(['prices' => function ($query) {
                    $query->select('id', 'product_spec_id', 'price')->where(['group_id' => $this->user()->group_id, 'status' => 1]);
                }]);
            }])->withCount(['product' => function ($query) {
                $query->where('status', 1);
            }])->orderBy('id', 'desc')->get();
        }
    }

    public function order($address_id, $products_id, $type = 1)
    {
        $user_id = $this->user()->id;
        if ($type) {
            $cart_id = explode(',', $products_id);
            $carts = UserCart::select('id', 'product_id', 'product_spec_id', 'number')->whereIn('id', $cart_id)->where('user_id', $this->user()->id)->with(['product_spec' => function ($query) {
                $query->select('id', 'price', 'weight')->with(['prices' => function ($query) {
                    $query->select('id', 'product_spec_id', 'price')->where('status', 1);
                }]);
            }, 'product' => function ($query) {
                $query->select('id', 'title', 'short_title');
            }])->orderBy('id', 'desc')->get();
        } else {
            $cart = (object)null;
            $cart->product_spec_id = $products_id;
            $cart->product_spec = ProductSpec::select('id', 'product_id', 'price', 'weight')->where('id', $products_id)->with(['prices' => function ($query) {
                $query->select('id', 'product_spec_id', 'price')->where('status', 1);
            }])->first();
            $cart->product_id = $cart->product_spec->product_id;
            $cart->product = Product::select('id', 'title', 'short_title')->where('id', $cart->product_id)->first();
            $cart->number = $this->request->input('number', 1);
            $carts = collect([$cart]);
        }
        $price['product_number'] = 0;
        $price['product_price'] = 0.00;
        $price['express'] = [];
        $price['coupon'] = [];
        $price['address'] = '';
        $product_weight = 0;
        $coupons_price = [];
        foreach ($carts as $cart) {
            $group_price = $cart->product_spec->prices()->where('group_id', $this->user()->group_id)->value('price');
            $product_price = $group_price ? $group_price : $cart->product_spec->price;
            $price['product_number'] += $cart->number;
            $price['product_price'] += $product_price * $cart->number;
            $product_weight += $cart->product_spec->weight * $cart->number;
            if (!isset($coupons_price[$cart->product_id])) {
                $coupons_price[$cart->product_id] = 0;
            }
            $coupons_price[$cart->product_id] += $product_price * $cart->number;
        }
        //用户地址
        $user_address = UserAddress::select('name', 'mobile', 'email', 'province', 'city', 'district', 'address')->where('id', $address_id)->where('user_id', $this->user()->id)->first();
        $price['address'] = $user_address;
        //邮费
        $product_expresses = Express::select('id', 'name', 'content')->where('status', 1)->orderBy('id', 'desc')->get();
        if (!$product_expresses->isEmpty() && $product_weight > 0) {
            foreach ($product_expresses as $product_express) {
                foreach ($product_express->content as $v) {
                    if (strpos($v['province'], $user_address->province) !== false) {
                        $price['express'][$product_express->id]['name'] = $product_express->name;
                        if ($product_weight <= $v['weight']) {
                            $price['express'][$product_express->id]['price'] = $v['price'];
                        } else {
                            $price['express'][$product_express->id]['price'] = $v['price'] + ceil(($product_weight - $v['weight']) / $v['weight2']) * $v['price2'];
                        }
                        break;
                    }
                }
            }
        }
        //优惠券
        if ($coupons_price) {
            $coupons = Coupon::select('id', 'name', 'money', 'price')->where([['status', '=', 1], ['begin_time', '<=', date('Y-m-d H:i:s')], ['end_time', '>=', date('Y-m-d H:i:s')]])->with(['products' => function ($query) {
                $query->select('id');
            }])->whereHas('items', function ($query) use ($user_id) {
                $query->where('user_id', $user_id)->where('status', 1);
            })->get();
            if ($coupons->isNotEmpty()) {
                $product_ids = array_keys($coupons_price);
                foreach ($coupons as $coupon) {
                    $coupon_price = 0;
                    $intersect_ids = $coupon->products()->count() == 0 ? $product_ids : array_intersect($product_ids, $coupon->products()->pluck('id')->toArray());
                    if ($intersect_ids) {
                        foreach ($intersect_ids as $id) {
                            $coupon_price += $coupons_price[$id];
                        }
                    }
                    if ($coupon->price <= $coupon_price) {
                        $price['coupon'][$coupon->id] = ['name' => $coupon->name, 'money' => $coupon->money];
                    }
                }
            }
        }
        if ($this->request->isMethod('post')) {
            $order_product = [];
            $pay_type = $this->request->input('pay_type');
            if (count($carts) == 0) {
                return $this->errorBadRequest('购物车不能为空');
            }
            foreach ($carts as $k => $cart) {
                $product_id = $cart->product_id;
                $product_spec_id = $cart->product_spec_id;
                $number = $cart->number;
                DB::beginTransaction();
                try {
                    $product_spec = ProductSpec::select('id', 'product_id', 'image', 'price', 'weight', 'stock', 'day', 'limit', 'pay')->where(['id' => $product_spec_id, 'product_id' => $product_id, 'status' => 1])->with(['product' => function ($query) {
                        $query->select('id', 'title', 'short_title', 'image', 'type');
                    }, 'prices' => function ($query) {
                        $query->select('id', 'product_spec_id', 'price')->where('status', 1);
                    }])->sharedLock()->first();
                    if ($product_spec) {
                        if ($number > $product_spec->stock) {
                            throw new \Exception('库存不足');
                        }
                        $mark = 0;
                        if ($product_spec->day >= 1 && $product_spec->limit >= 1) {
                            $first_created_at = DB::table('user_order')
                                ->join('user_order_product', 'user_order.id', '=', 'user_order_product.user_order_id')
                                ->where('user_order.user_id', $user_id)
                                ->where('user_order.status', '<>', 'canceled')
                                ->where('user_order_product.product_spec_id', $product_spec_id)
                                ->where('user_order_product.mark', 1)
                                ->orderBy('user_order_product.created_at', 'desc')
                                ->value('user_order_product.created_at');
                            if ($first_created_at) {
                                $product_spec_count = DB::table('user_order')
                                    ->join('user_order_product', 'user_order.id', '=', 'user_order_product.user_order_id')
                                    ->where('user_order.user_id', $user_id)
                                    ->where('user_order.status', '<>', 'canceled')
                                    ->where('user_order_product.product_spec_id', $product_spec_id)
                                    ->where('user_order_product.created_at', '>=', date('Y-m-d', strtotime($first_created_at)))
                                    ->where('user_order_product.created_at', '<', date('Y-m-d', strtotime('+' . $product_spec->day . ' day', strtotime($first_created_at))))
                                    ->count();
                                if (date('Y-m-d') >= date('Y-m-d', strtotime($first_created_at)) && date('Y-m-d') < date('Y-m-d', strtotime('+' . $product_spec->day . ' day', strtotime($first_created_at)))) {
                                    if ($product_spec_count + 1 > $product_spec->limit) {
                                        throw new \Exception('在' . $product_spec->day . '天内最多购买' . $product_spec->limit . '件');
                                    }
                                } else {
                                    if ($number > $product_spec->limit) {
                                        throw new \Exception('不能超出最大购买数量');
                                    } else {
                                        $mark = 1;
                                    }
                                }
                            } else {
                                if ($number > $product_spec->limit) {
                                    throw new \Exception('已经超出最大购买数量');
                                } else {
                                    $mark = 1;
                                }
                            }
                        }
                        if ($product_spec->day == 0 && $product_spec->limit >= 1) {
                            $product_spec_count = DB::table('user_order')
                                ->join('user_order_product', 'user_order.id', '=', 'user_order_product.user_order_id')
                                ->where('user_order.user_id', $user_id)
                                ->where('user_order.status', '<>', 'canceled')
                                ->where('user_order_product.product_spec_id', $product_spec_id)
                                ->count();
                            if ($product_spec_count + 1 > $product_spec->limit || $number > $product_spec->limit) {
                                throw new \Exception('每人最多购买' . $product_spec->limit . '件');
                            }
                        }
                    } else {
                        throw new \Exception('产品规格不存在');
                    }
                    $order_product[$k]['product_id'] = $product_id;
                    $order_product[$k]['product_spec_id'] = $product_spec_id;
                    $order_product[$k]['short_title'] = $product_spec->product->short_title;
                    $order_product[$k]['title'] = $product_spec->product->title;
                    $order_product[$k]['type'] = $product_spec->product->type;
                    $order_product[$k]['spec'] = [];
                    $spec_values = $product_spec->spec_values()->select('id', 'spec_id', 'name')->with(['spec' => function ($q) {
                        $q->select('id', 'name');
                    }])->get();
                    foreach ($spec_values as $spec_value) {
                        $order_product[$k]['spec'][] = $spec_value->spec->name . ':' . $spec_value->name;
                    }
                    $order_product[$k]['spec'] = implode(',', $order_product[$k]['spec']);
                    $order_product[$k]['image'] = $product_spec->image;
                    $order_product[$k]['price'] = $product_spec->price;
                    $order_product[$k]['price_group'] = $product_spec->prices()->where('group_id', $this->user()->group_id)->value('price');
                    $order_product[$k]['weight'] = $product_spec->weight;
                    $order_product[$k]['pay'] = $product_spec->pay;
                    $order_product[$k]['number'] = $number;
                    $order_product[$k]['mark'] = $mark;
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return $this->errorBadRequest($e->getMessage());
                }
            }
            $data = [];
            $data['user_id'] = $user_id;
            $data['trade_no'] = date('YmdHis') . mt_rand(1000, 9999);
            $data['status'] = 'confirm';
            //地址
            $data['address_id'] = $address_id;
            $data['address_name'] = $price['address']->name;
            $data['address_mobile'] = $price['address']->mobile;
            $data['address_email'] = $price['address']->email;
            $data['address_province'] = $price['address']->province;
            $data['address_city'] = $price['address']->city;
            $data['address_district'] = $price['address']->district;
            $data['address_address'] = $price['address']->address;
            //运费
            $express_id = $this->request->input('express_id');
            if ($express_id && isset($price['express'][$express_id])) {
                $data['express_id'] = $express_id;
                $data['express_name'] = $price['express'][$express_id]['name'];
                $data['express_price'] = $price['express'][$express_id]['price'];
            }
            //优惠券
            $coupon_id = $this->request->input('coupon_id');
            if ($coupon_id && isset($price['coupon'][$coupon_id])) {
                $data['coupon_id'] = $coupon_id;
                $data['coupon_name'] = $price['coupon'][$coupon_id]['name'];
                $data['coupon_money'] = $price['coupon'][$coupon_id]['money'];
                $coupon_item = CouponItem::where(['coupon_id' => $coupon_id, 'user_id' => $user_id, 'status' => 1])->first();
                $coupon_item->update(['used_at' => date('Y-m-d H:i:s'), 'status' => 2]);
                $data['coupon_item_id'] = $coupon_item->id;
                if ($price['product_price'] > $data['coupon_money']) {
                    $price['product_price'] -= $data['coupon_money'];
                }
            }
            //产品数量
            $data['product_number'] = $price['product_number'];
            //产品费用
            $data['product_price'] = $price['product_price'];
            //支付方式、状态
            $data['pay_type'] = $pay_type;
            $data['pay_status'] = 'unpaid';
            $data['remark'] = $this->request->input('remark');
            //创建订单
            $order = UserOrder::create($data);
            sendOrderLog($this->user()->id, $order->id, 'user', 'create', '创建订单');
            //创建订单产品
            foreach ($order_product as $product) {
                //减库存
                ProductSpec::where(['id' => $product['product_spec_id'], 'product_id' => $product['product_id'], 'status' => 1])->decrement('stock', $product['number']);
                UserOrderProduct::create(array_add($product, 'user_order_id', $order->id));
            }
            //消息提醒
            sendMsg($user_id, '生成订单提醒', 'order', '你购买的订单(订单号：' . $order->trade_no . ')已经生成');
            //清空购物车
            if ($type) {
                UserCart::whereIn('id', $cart_id)->delete();
            }
            return ['order_id' => $order->id, 'trade_no' => $order->trade_no];
        } else {
            return $price;
        }
    }
}