<?php

namespace App\Http\Controllers\Admin;

use App\Transformers\UserTransformer;
use App\Models\User;
use App\Models\UserOrder;
use App\Models\UserRemind;

class UserController extends AdminController
{
    public function index()
    {
        $where['nickname'] = $this->request->get('nickname');
        $where['openid'] = $this->request->get('openid');
        $where['id'] = $this->request->get('id');
        $where['status'] = $this->request->get('status');
        $where['subscribe'] = $this->request->get('subscribe');
        $where['active'] = $this->request->get('active', 0);
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
        })->orderBy($order_field, $order_type);
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
            foreach ($list as $key => $user) {
                $list[$key]->active = (int)in_array($user->openid, $where['active_user']);
                $remind = UserRemind::where('user_id', $user->id)->first();
                $list[$key]->remind = is_null($remind) ? 0 : 1;
            }
            return $this->paginator($list, new UserTransformer());
        }
    }

    public function get($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorNotFound();
        }
        $remind = UserRemind::where('user_id', $id)->first();
        $user->remind = is_null($remind) ? 0 : 1;
        return $this->item($user, new UserTransformer());
    }

    public function put($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorNotFound();
        }
        $data = $this->request->input();
        $user->update(array_except($data, ['id', 'admin', 'remind', 'tags', 'catalogs', 'subscribe_time']));
        if (isset($data['tags'])) {
            $user->tags()->sync(explode(',', $data['tags']));
        }
        if (isset($data['catalogs'])) {
            $user->catalogs()->sync(explode(',', $data['catalogs']));
        }
        if ($config = array_only($data, ['admin', 'remind'])) {
            if (empty($user->config)) {
                $user->config = [];
            }
            $configArr = $user->config;
            foreach ($config as $key => $value) {
                $configArr[$key] = $config[$key];
            }
            $user->update(['config' => $configArr]);
        }
        return $this->created();
    }

    public function coin($id)
    {
        $coin = $this->request->input('coin');
        $remark = $this->request->input('remark');
        if (is_numeric($coin) && $coin != 0) {
            changeCoin($id, $coin, 'admin', $this->user()->id, $remark ?: '管理员修改原力');
            //消息提醒
            sendMsg($id, '管理员修改原力提醒', 'coin', $remark ?: '管理员已为您' . ($coin > 0 ? '增加' : '减少') . abs($coin) . '原力！');
        }
        return $this->created();
    }

    public function sign($id)
    {
        $date = $this->request->input('date');
        if ($date > date('Y-m-d')) {
            return $this->errorBadRequest($date . '不能补签！');
        }
        if ($date != date('Y-m-d') && UserSign::where('user_id', $id)->where('date', date('Y-m-d'))->count() == 0) {
            return $this->errorBadRequest('请先补签今天！');
        }
        if (UserSign::where('user_id', $id)->where('date', $date)->count()) {
            return $this->errorBadRequest('已经补签过了！');
        }
        //发送到消息队列
        dispatch((new \App\Jobs\SignAdd(User::find($id), $this->request->ip(), $date))->onQueue('sign'));
        //消息提醒
        sendMsg($id, '管理员补签成功提醒', 'sign', '恭喜您，管理员已为你' . $date . '日补签成功！');
        return $this->created();
    }

    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorNotFound();
        }
        $user->delete();
        return $this->noContent();
    }
}