<?php

namespace App\Http\Controllers;

use App\Models\MsgSession;
use App\Transformers\MsgSessionTransformer;
use App\Models\User;

class MsgController extends Controller
{
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $session = MsgSession::findOrFail($id);
            $session->msgs()->where('is_read', 0)->update(['is_read' => 1]);
            return $this->item($session, new MsgSessionTransformer());
        }
        $appid = $this->request->get('appid', 'wxaa319a8242b02ae2');
        $start = $this->request->get('start', 0);
        $row = $this->request->get('row', 25);
        $keyword = $this->request->get('keyword');

        $MsgSession = new MsgSession;
        if(!empty($keyword)){
            if (is_numeric($keyword)) {
                $userId = (array)User::find($keyword)->id;
            } else {
                $userId = User::where('nickname', 'like', $keyword . '%')->pluck('id')->toArray();
            }
            $MsgSession = $MsgSession->whereIn('user_id', $userId);
        }
        $sessions = $MsgSession->where('appid', $appid)
        ->withCount(['msgs' => function ($query) {
            $query->where('is_read', 0);
        }])
        ->orderBy('updated_at', 'desc')
        ->skip($start)
        ->take($row)
        ->get();
        return $this->collection($sessions, new MsgSessionTransformer());
    }

    public function post($session_id, $user_id)
    {
        $content = $this->request->input('content');
        $type = $this->request->input('type', 'text');
        if ($session_id) {
            $session = MsgSession::where(['id' => $session_id, 'user_id' => $user_id])->first();
            if ($session && sendStaffMsg($type, $content, $session->user->openid, ['appid' => \Auth::user()->cur_appid])) {
                $session->update(['updated_at' => date('Y-m-d H:i:s')]);
                $session->msgs()->create(['user_id' => $this->user()->id, 'code' => 2, 'type' => $type, 'content' => $content]);
            } else {
                return $this->errorBadRequest('回复失败');
            }
        } else {
            MsgSession::updateOrCreate(['user_id' => $user_id], ['updated_at' => date('Y-m-d H:i:s')]);
        }
        return $this->created();
    }

    public function delete($session_id)
    {
        MsgSession::findOrFail($session_id)->delete();
        return $this->noContent();
    }
}
