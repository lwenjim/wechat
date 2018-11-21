<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coupon;
use App\Models\KefuConnMsg;
use App\Models\Product;
use App\Models\User;
use App\Models\UserCart;
use App\Models\UserOrder;
use App\Models\UserOrderProduct;
use App\Models\UserStat;
use App\Transformers\ProductTransformer;
use Carbon\Carbon;

class CountController extends AdminController
{
    function main($type)
    {
        $data = [];
        if ($type == 'dau') {
            //日活
            $data['dau'] = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->distinct()->count('user_id');
            $dau = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->distinct()->count('user_id');
            $data['dod'] = sprintf("%.2f", ($data['dau'] - $dau) / ($dau > 0 ? $dau : 1));
        }
        if ($type == 'wau') {
            //周活
            $data['wau'] = UserStat::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfWeek()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->distinct()->count('user_id');
            $wau = UserStat::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->startOfWeek()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->endOfWeek()->toDateTimeString())->distinct()->count('user_id');
            $data['wow'] = sprintf("%.2f", ($data['wau'] - $wau) / ($wau > 0 ? $wau : 1));
        }
        if ($type == 'mau') {
            //月活
            $data['mau'] = UserStat::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfMonth()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->distinct()->count('user_id');
            $mau = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->startOfMonth()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->endOfMonth()->toDateTimeString())->distinct()->count('user_id');
            $data['mom'] = sprintf("%.2f", ($data['mau'] - $mau) / ($mau > 0 ? $mau : 1));
        }
        if ($type == 'wechat_user') {
            //微信粉丝数
            $data['wechat_user'] = User::where('openid_at', '>=', date('Y-m-d'))->count();
            $wechat_user = User::where('openid_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('openid_at', '<=', date('Y-m-d'))->count();
            $data['wechat_user_vary'] = $data['wechat_user'] - $wechat_user;
        }
        if ($type == 'miniapp_user') {
            //小程序粉丝数
            $data['miniapp_user'] = User::where('openid_at', '>=', date('Y-m-d'))->count();
            $miniapp_user = User::where('openid_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('openid_at', '<=', date('Y-m-d'))->count();
            $data['miniapp_user_vary'] = $data['miniapp_user'] - $miniapp_user;
        }
        if ($type == 'retain') {
            //留存数
            $retain = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->distinct()->pluck('user_id')->toArray();
            $retain_yesterday = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->distinct()->pluck('user_id')->toArray();
            $retain_beforeday = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-3 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-2 day')))->distinct()->pluck('user_id')->toArray();
            $data['retain'] = sprintf("%.2f", count(array_intersect($retain, $retain_yesterday)) / (count($retain_yesterday) > 0 ? count($retain_yesterday) : 1));
            $data['retain_vary'] = sprintf("%.2f", (count($retain_yesterday) - count($retain_beforeday)) / (count($retain_beforeday) > 0 ? count($retain_beforeday) : 1));
        }
        if ($type == 'pv') {
            //pv
            $data['pv'] = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->count();
            $pv = UserStat::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->count();
            $data['pv_vary'] = sprintf("%.2f", ($data['pv'] - $pv) / ($pv > 0 ? $pv : 1));
        }
        //gmv
        if ($type == 'gmv_d') {
            $data['gmv_d'] = UserOrder::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->sum('product_price');
            $gmv_d = UserOrder::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->sum('product_price');
            $data['gmv_dod'] = sprintf("%.2f", ($data['gmv_d'] - $gmv_d) / ($gmv_d > 0 ? $gmv_d : 1));
        }
        if ($type == 'gmv_w') {
            $data['gmv_w'] = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfWeek()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->sum('product_price');
            $gmv_w = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->startOfWeek()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->endOfWeek()->toDateTimeString())->sum('product_price');
            $data['gmv_wow'] = sprintf("%.2f", ($data['gmv_w'] - $gmv_w) / ($gmv_w > 0 ? $gmv_w : 1));
        }
        if ($type == 'gmv_m') {
            $data['gmv_m'] = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfMonth()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->sum('product_price');
            $gmv_m = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->startOfMonth()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->endOfMonth()->toDateTimeString())->sum('product_price');
            $data['gmv_mom'] = sprintf("%.2f", ($data['gmv_m'] - $gmv_m) / ($gmv_m > 0 ? $gmv_m : 1));
        }
        //gmp
        if ($type == 'gmp_d') {
            $data['gmp_d'] = UserOrder::where('pay_status', 'paid')->where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->sum('product_price');
            $gmp_d = UserOrder::where('pay_status', 'paid')->where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->sum('product_price');
            $data['gmp_dod'] = sprintf("%.2f", ($data['gmp_d'] - $gmp_d) / ($gmp_d > 0 ? $gmp_d : 1));
        }
        if ($type == 'gmp_w') {
            $data['gmp_w'] = UserOrder::where('pay_status', 'paid')->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfWeek()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->sum('product_price');
            $gmp_w = UserOrder::where('pay_status', 'paid')->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->startOfWeek()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->endOfWeek()->toDateTimeString())->sum('product_price');
            $data['gmp_wow'] = sprintf("%.2f", ($data['gmp_w'] - $gmp_w) / ($gmp_w > 0 ? $gmp_w : 1));
        }
        if ($type == 'gmp_m') {
            $data['gmp_m'] = UserOrder::where('pay_status', 'paid')->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfMonth()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->sum('product_price');
            $gmp_m = UserOrder::where('pay_status', 'paid')->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->startOfMonth()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->endOfMonth()->toDateTimeString())->sum('product_price');
            $data['gmp_mom'] = sprintf("%.2f", ($data['gmp_m'] - $gmp_m) / ($gmp_m > 0 ? $gmp_m : 1));
        }
        //gmn
        if ($type == 'gmn_d') {
            $data['gmn_d'] = UserOrder::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->count();
            $gmn_d = UserOrder::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->count();
            $data['gmn_dod'] = sprintf("%.2f", ($data['gmn_d'] - $gmn_d) / ($gmn_d > 0 ? $gmn_d : 1));
        }
        if ($type == 'gmn_w') {
            $data['gmn_w'] = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfWeek()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->count();
            $gmn_w = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->startOfWeek()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->endOfWeek()->toDateTimeString())->count();
            $data['gmn_wow'] = sprintf("%.2f", ($data['gmn_w'] - $gmn_w) / ($gmn_w > 0 ? $gmn_w : 1));
        }
        if ($type == 'gmn_m') {
            $data['gmn_m'] = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfMonth()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->count();
            $gmn_m = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->startOfMonth()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->endOfMonth()->toDateTimeString())->count();
            $data['gmn_mom'] = sprintf("%.2f", ($data['gmn_m'] - $gmn_m) / ($gmn_m > 0 ? $gmn_m : 1));
        }
        //gma
        if ($type == 'gma_d') {
            $data['gma_d'] = UserOrder::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->avg('product_price');
            $gma_d = UserOrder::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->avg('product_price');
            $data['gma_dod'] = sprintf("%.2f", ($data['gma_d'] - $gma_d) / ($gma_d > 0 ? $gma_d : 1));
        }
        if ($type == 'gma_w') {
            $data['gma_w'] = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfWeek()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->avg('product_price');
            $gma_w = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->startOfWeek()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subWeek()->endOfWeek()->toDateTimeString())->avg('product_price');
            $data['gma_wow'] = sprintf("%.2f", ($data['gma_w'] - $gma_w) / ($gma_w > 0 ? $gma_w : 1));
        }
        if ($type == 'gma_m') {
            $data['gma_m'] = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfMonth()->toDateTimeString())->where('created_at', '<=', date('Y-m-d'))->avg('product_price');
            $gma_m = UserOrder::where('created_at', '>=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->startOfMonth()->toDateTimeString())->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->subMonth()->endOfMonth()->toDateTimeString())->avg('product_price');
            $data['gma_mom'] = sprintf("%.2f", ($data['gma_m'] - $gma_m) / ($gma_m > 0 ? $gma_m : 1));
        }
        return $data;
    }

    function user()
    {
        $begin_date = $this->request->get('begin_date', date('Y-m-d', strtotime('-1 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));
        $export = $this->request->get('export', 0);
        $export_data = [];
        $data = [];
        if ($begin_date < $end_date) {
            $begin_time = strtotime($begin_date);
            $end_time = strtotime($end_date);
            $day = ceil(($end_time - $begin_time) / 86400);
            for ($i = 0; $i <= $day; $i++) {
                $date = date('Y-m-d', $begin_time + 86400 * $i);
                $export_data[$i]['日期'] = $date;

                $wechat_add = User::where('openid_at', '>', $date)->where('openid_at', '<', date('Y-m-d', strtotime($date) + 86400))->count();
                $export_data[$i]['微信用户增长数'] = $wechat_add;
                $data['wechat_add']['xAxis'][] = $date;
                $data['wechat_add']['series'][] = $wechat_add;

                $miniapp_add = User::where('openid_at', '>', $date)->where('openid_at', '<', date('Y-m-d', strtotime($date) + 86400))->count();
                $export_data[$i]['小程序用户增长数'] = $miniapp_add;
                $data['miniapp_add']['xAxis'][] = $date;
                $data['miniapp_add']['series'][] = $miniapp_add;

                $wechat_all = User::where('openid_at', '<', $date)->count();
                $export_data[$i]['微信用户累积数'] = $wechat_all;
                $data['wechat_all']['xAxis'][] = $date;
                $data['wechat_all']['series'][] = $wechat_all;

                $miniapp_all = User::where('openid_at', '<', $date)->count();
                $export_data[$i]['小程序用户累积数'] = $miniapp_all;
                $data['miniapp_all']['xAxis'][] = $date;
                $data['miniapp_all']['series'][] = $miniapp_all;

                $wechat_active = (int)app('redis')->hget('mornight:active-1', $date);
                $export_data[$i]['微信活跃用户数'] = $wechat_active;
                $data['wechat_active']['xAxis'][] = $date;
                $data['wechat_active']['series'][] = $wechat_active;

                $miniapp_active = (int)app('redis')->hget('mornight:active-2', $date);
                $export_data[$i]['小程序活跃用户数'] = $miniapp_active;
                $data['miniapp_active']['xAxis'][] = $date;
                $data['miniapp_active']['series'][] = $miniapp_active;
            }
            if ($export) {
                return app('excel')->create(date('Y-m-d H:i:s') . '用户统计数据', function ($excel) use ($export_data) {
                    $excel->sheet('用户统计数据', function ($sheet) use ($export_data) {
                        $sheet->fromArray($export_data, 'null', 'A1', true, true);
                    });
                })->export('xlsx');
            }
        }
        return $data;
    }

    function order()
    {
        $begin_date = $this->request->get('begin_date', date('Y-m-d', strtotime('-1 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));
        $data = [];
        if ($begin_date <= $end_date) {
            $begin_time = strtotime($begin_date);
            $end_time = strtotime($end_date);
            $day = ceil(($end_time - $begin_time) / 86400);
            for ($i = 0; $i <= $day; $i++) {
                $date = date('Y-m-d', $begin_time + 86400 * $i);

                $data['gmv']['xAxis'][] = $date;
                $data['gmv']['series'][] = UserOrder::withTrashed()->where('created_at', '>', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->sum('product_price');

                $data['gmp']['xAxis'][] = $date;
                $data['gmp']['series'][] = UserOrder::withTrashed()->where('pay_status', 'paid')->where('created_at', '>', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->sum('product_price');

                $data['gmc']['xAxis'][] = $date;
                $data['gmc']['series'][] = UserOrder::withTrashed()->where('coupon_id', '>', 0)->where('created_at', '>', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->sum('product_price');

                $data['gma']['xAxis'][] = $date;
                $data['gma']['series'][] = UserOrder::withTrashed()->where('created_at', '>', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->avg('product_price');

                $data['gmn']['xAxis'][] = $date;
                $data['gmn']['series'][] = UserOrder::withTrashed()->where('created_at', '>', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->sum('product_number');

                $data['kefu']['xAxis'][] = $date;
                $data['kefu']['series'][] = KefuConnMsg::where('code', 1)->where('created_at', '>', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->count();
            }
        }
        return $data;
    }

    function stat()
    {
        $begin_date = $this->request->get('begin_date', date('Y-m-d', strtotime('-1 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));
        $path = $this->request->get('path');
        $hour = $this->request->get('hour', 0);
        if ($begin_date <= $end_date) {
            $begin_time = strtotime($begin_date);
            $end_time = strtotime($end_date);
            $day = ceil(($end_time - $begin_time) / 86400);
            for ($i = 0; $i <= $day; $i++) {
                $date = date('Y-m-d', $begin_time + 86400 * $i);
                $data['pv']['xAxis'][] = $date;
                $data['uv']['xAxis'][] = $date;
                if ($hour) {
                    $pvs = [];
                    $uvs = [];
                    $stats = UserStat::select('id', 'user_id', 'created_at')->where('created_at', '>=', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->when($path, function ($query) use ($path) {
                        return $query->where('path', $path);
                    })->get();
                    for ($i = 0; $i < 24; $i++) {
                        if ($i < 10) {
                            $time = $date . ' 0' . $i . ':00:00';
                        } else {
                            $time = $date . ' ' . $i . ':00:00';
                        }
                        $pvs[$i] = $stats->where('created_at', '>', $time)->where('created_at', '<', date('Y-m-d H:i:s', strtotime($time) + 3600))->count();
                        $uvs[$i] = $stats->where('created_at', '<', date('Y-m-d H:i:s', strtotime($time) + 3600))->unique('user_id')->count();
                    }
                    $data['pv']['series'][] = $pvs;
                    $data['uv']['series'][] = $uvs;
                } else {
                    $data['pv']['series'][] = UserStat::where('created_at', '>=', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->when($path, function ($query) use ($path) {
                        return $query->where('path', $path);
                    })->count();
                    $data['uv']['series'][] = UserStat::where('created_at', '>=', $date)->where('created_at', '<', date('Y-m-d', strtotime($date) + 86400))->when($path, function ($query) use ($path) {
                        return $query->where('path', $path);
                    })->distinct()->count('user_id');
                }
            }
        }
        return $data;
    }

    function product()
    {
        $catalog = $this->request->get('catalog');
        $begin_date = $this->request->get('begin_date', date('Y-m-d', strtotime('-1 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));
        $order = $this->request->get('order_count', 'created_at,desc');
        list($orderBy, $orderType) = explode(',', $order);
        $products = Product::select('id', 'short_title', 'image', \DB::raw("(select SUM(user_order_product.number) from user_order_product where product.id = user_order_product.product_id) as order_count"), \DB::raw("(select SUM(user_order_product.number*user_order_product.price) from user_order_product where product.id = user_order_product.product_id) as order_price"), \DB::raw("(select MIN(product_spec.price) from product_spec where product.id = product_spec.product_id) as product_price"))
            ->when($catalog, function ($query) use ($catalog) {
                return $query->whereHas('catalogs', function ($query) use ($catalog) {
                    $query->where('id', $catalog);
                });
            })->whereHas('user_order_products', function ($query) use ($begin_date, $end_date) {
                $query->whereBetween('created_at', [$begin_date, $end_date]);
            })->orderBy($orderBy, $orderType)->paginate();
        return $this->paginator($products, new ProductTransformer());
    }

    function coupon()
    {
        $begin_date = $this->request->get('begin_date', date('Y-m-d', strtotime('-1 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));
        return Coupon::select('id', 'name', 'image', 'money', 'price', \DB::raw("(select SUM(user_order.product_price) from user_order where coupon.id = user_order.coupon_id) as order_price"))->withCount(['items', 'items AS items0' => function ($query) {
            $query->where('status', 0);
        }, 'items AS items1' => function ($query) {
            $query->where('status', 1);
        }, 'items AS items2' => function ($query) {
            $query->where('status', 2);
        }, 'items AS items3' => function ($query) {
            $query->where('status', 3);
        }])->where('status', 1)->whereBetween('created_at', [$begin_date, $end_date])->paginate();
    }

    function order2pay()
    {
        $paid = UserOrder::withTrashed()->where('pay_status', 'paid')->where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->count();
        $unpaid = UserOrder::withTrashed()->where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->count();
        $vary = sprintf("%.2f", $paid / ($unpaid > 0 ? $unpaid : 1));
        $paid = UserOrder::withTrashed()->where('pay_status', 'paid')->where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->count();
        $unpaid = UserOrder::withTrashed()->where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->count();
        $vary2 = sprintf("%.2f", $paid / ($unpaid > 0 ? $unpaid : 1));
        $data['rate'] = $vary;
        $data['vary'] = sprintf("%.2f", ($vary - $vary2) / ($vary2 > 0 ? $vary2 : 1));
        return $data;
    }

    function cart2order()
    {
        $cart = UserCart::withTrashed()->where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->sum('number');
        $order = UserOrderProduct::where('created_at', '>=', date('Y-m-d', strtotime('-1 day')))->where('created_at', '<=', date('Y-m-d'))->sum('number');
        $vary = sprintf("%.2f", $order / ($cart > 0 ? $cart : 1));
        $cart = UserCart::withTrashed()->where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->sum('number');
        $order = UserOrderProduct::where('created_at', '>=', date('Y-m-d', strtotime('-2 day')))->where('created_at', '<=', date('Y-m-d', strtotime('-1 day')))->sum('number');
        $vary2 = sprintf("%.2f", $cart / ($order > 0 ? $order : 1));
        $data['rate'] = $vary;
        $data['vary'] = sprintf("%.2f", ($vary - $vary2) / ($vary2 > 0 ? $vary2 : 1));
        return $data;
    }

    function portrait()
    {
        $date = $this->request->get('date', date('Y-m-d', strtotime('-1 day')));
        return app('wechat')->mini_program->stats->userPortrait($date, $date);
    }

    function source()
    {
        $date = $this->request->get('date', date('Y-m-d', strtotime('-1 day')));
        return app('wechat')->mini_program->stats->visitDistribution($date, $date);
    }

    function wechat()
    {
        $begin_date = $this->request->get('begin_date', date('Y-m-d', strtotime('-2 day')));
        $end_date = $this->request->get('end_date', date('Y-m-d', strtotime('-1 day')));
        $data = [];
        if ($begin_date < $end_date) {
            $stats = app('wechat')->stats;
            $userSummary = $stats->userSummary($begin_date, $end_date)->list;
            $all = [];
            foreach ($userSummary as $value) {
                if (!isset($all[$value['ref_date']]['new_user'])) {
                    $all[$value['ref_date']]['new_user'] = 0;
                }
                if (!isset($all[$value['ref_date']]['cancel_user'])) {
                    $all[$value['ref_date']]['cancel_user'] = 0;
                }
                $data['wechat_user_change'][$value['user_source']]['xAxis'][] = $value['ref_date'];
                $data['wechat_user_change'][$value['user_source']]['series'][] = ['new_user' => $value['new_user'], 'cancel_user' => $value['cancel_user']];
                $all[$value['ref_date']]['new_user'] += $value['new_user'];
                $all[$value['ref_date']]['cancel_user'] += $value['cancel_user'];
            }
            $data['wechat_user_change']['all']['xAxis'] = array_keys($all);
            $data['wechat_user_change']['all']['series'] = array_values($all);
            $userCumulate = $stats->userCumulate($begin_date, $end_date)->list;
            foreach ($userCumulate as $value) {
                $data['wechat_user_total']['xAxis'][] = $value['ref_date'];
                $data['wechat_user_total']['series'][] = $value['cumulate_user'];
            }
        }
        return $data;
    }
}
