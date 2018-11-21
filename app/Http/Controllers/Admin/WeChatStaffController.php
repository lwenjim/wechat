<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\WeChatStaff;
use App\Jobs\Staff;
use App\Transformers\WeChatStaffTransformer;

class WeChatStaffController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $where['status'] = $this->request->get('status');
        $where['order'] = $this->request->get('order', 'created_at,desc');
        list($order_field, $order_type) = explode(',', $where['order']);
        $staffs = WeChatStaff::where(function ($query) use ($where) {
            if ($where['name']) {
                $query->where('name', 'like', '%' . $where['name'] . '%');
            }
            if ($where['status'] != '') {
                $query->where('status', $where['status']);
            }
        })->orderBy($order_field, $order_type)->paginate();
        return $this->paginator($staffs, new WeChatStaffTransformer());
    }

    function form($id = 0)
    {
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|max:255',
            'wechat_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        if ($data['reply'] == 'news') {
            $data['content'] = json_encode($data['content']);
        }
        WeChatStaff::updateOrCreate(['id' => $id], $data);
        return $this->created();
    }

    function get($id)
    {
        $staff = WeChatStaff::find($id);
        if (!$staff) {
            return $this->errorNotFound();
        }
        return $this->item($staff, new WeChatStaffTransformer());
    }

    function delete($id)
    {
        $staff = WeChatStaff::find($id);
        if (!$staff) {
            return $this->errorNotFound();
        }
        $staff->delete();
        return $this->noContent();
    }

    function send($id)
    {
        $staff = WeChatStaff::select('id', 'wechat_id', 'type', 'reply', 'tpl', 'content')->where('id', $id)->with(['wechat' => function ($query) {
            $query->select('id', 'appid');
        }])->first();
        if ($this->request->isMethod('post')) {
            $openid = $this->request->input('openid');
            $openid = explode("\n", trim($openid));
            $success_count = 0;
            $error_count = 0;
            if ($openid) {
                foreach ($openid as $v) {
                    if (sendStaffMsg($staff->reply, $staff->content, $v, 1, $staff->wechat->appid)) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                return ['msg' => '总数:' . ($success_count + $error_count) . '，成功数:' . $success_count . '，失败:' . $error_count . ($error_count > 0 ? '，失败原因:' . $e->getMessage() : '')];
            } else {
                return ['msg' => 'openid不能为空'];
            }
        } else {
            $redis = app('redis');
            $openid = getActiveUser($staff->wechat->appid);
            if ($staff->type != 'active') {
                $openid = User::where([$staff->type => 1, 'subscribe' => 1])->whereIn('openid', $openid)->pluck('openid')->toArray();
            }
            $count = 0;
            $redis->hset('staff-msg-' . $id, 'count', count($openid));
            $redis->hset('staff-msg-' . $id, 'staff_success', 0);
            $redis->hset('staff-msg-' . $id, 'staff_fail', 0);
            $redis->hset('staff-msg-' . $id, 'tpl_success', 0);
            $redis->hset('staff-msg-' . $id, 'tpl_fail', 0);
            foreach ($openid as $v) {
                dispatch(new Staff($staff, $v, $staff->wechat->appid));
                $count++;
            }
            $staff->update(['all' => 1]);
            return ['msg' => '已群发' . $count . '条，请稍后刷新查看发送结果'];
        }
    }
}
