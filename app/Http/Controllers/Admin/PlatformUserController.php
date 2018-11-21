<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17 0017
 * Time: 10:46
 */

namespace App\Http\Controllers\Admin;


use App\Transformers\UserTransformer;
use App\Models\User;
use App\Models\UserOrder;
use App\Models\UserRemind;

class PlatformUserController extends AdminController
{
    public function index()
    {
        $where['nickname'] = $this->request->get('nickname');
        $where['openid'] = $this->request->get('openid');
        $where['id'] = $this->request->get('id');
        $where['status'] = $this->request->get('status');
        $where['subscribe'] = $this->request->get('subscribe');
        $where['active'] = $this->request->get('active', 0);
        $where['start_time'] = $this->request->get('start_time', 0);
        $where['end_time'] = $this->request->get('end_time', 0);
        $where['active_user'] = getActiveUser();
        $where['export'] = $this->request->get('export', 0);
        $where['order'] = $this->request->get('order', 'id,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $users = User::where(function ($query) use ($where) {
            if ($where['openid']) {
                $query->where('openid', 'like', '%' . $where['openid'] . '%');
            }
            if ($where['nickname']) {
                $query->where('nickname', 'like', $where['nickname'] . '%');
            }
            if ($where['id']) {
                $query->where('id', $where['id']);
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
            if ($where['subscribe'] != '') {
                $query->where('subscribe', $where['subscribe']);
            }
            if ($where['active']) {
                $query->whereIn('openid', $where['active_user']);
            }
            if (!empty($where['start_time'])) {
                $query->where('created_at', '>=', $where['start_time']);
            }
            if (!empty($where['end_time'])) {
                $query->where('created_at', '<=', $where['end_time']);
            }
        })->select('id', 'openid', 'nickname', 'headimgurl', 'mobile', 'sex', 'coin', 'blue_diamond', 'country', 'province', 'city', 'created_at')
            ->where('is_mini_user', 1)
            ->orderBy($order_field, $order_type);

        if ($where['export']) {
            $list = $users->get();
            $data = [];
            $sex = ['未知', '男', '女'];
            foreach ($list as $k => $v) {
                $data[$k]['序号'] = $v->id;
                $data[$k]['昵称'] = $v->nickname;
                $data[$k]['性别'] = $sex[$v->sex];
                $data[$k]['地区'] = $v->country . $v->province . $v->city;
                if ($v->subscribe) {
                    $data[$k]['关注时间'] = date('Y-m-d H:i:s', $v->subscribe_time);
                } else {
                    $data[$k]['关注时间'] = '';
                }
                $data[$k]['商品购买次数'] = UserOrder::where('user_id', $v->id)->count();
            }
            app('excel')->create(date('Y-m-d H:i:s') . '蜜拉蜜拉用户数据', function ($excel) use ($data) {
                $excel->sheet('蜜拉蜜拉用户数据', function ($sheet) use ($data) {
                    $sheet->fromArray($data, 'null', 'A1', true, true);
                });
            })->export('xlsx');
        } else {
            $list = $users->paginate();
            if (empty($list)) return [];
            foreach ($list as $key => $user) {
                $list[$key]->active = (int)in_array($user->openid, $where['active_user']);
                $remind = UserRemind::where('user_id', $user->id)->first();
                $list[$key]->remind = is_null($remind) ? 0 : 1;
            }
            return $this->paginator($list, new UserTransformer());
        }
    }
}