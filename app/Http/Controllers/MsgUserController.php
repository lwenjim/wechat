<?php
namespace App\Http\Controllers;
use App\Transformers\MsgUserTransformer;
use App\Models\MsgUser;

class MsgUserController extends AdminController
{
    function index()
    {
        $where['name'] = $this->request->get('name');
        $msgUser = MsgUser::where(function($query) use ($where) {
            if($where['name']){
                $query->where('name','like','%'.$where['name'].'%');
            }
        })->orderBy('id','desc')->paginate();
        return $this->paginator($msgUser, new MsgUserTransformer());
    }

    function get($id)
    {
        $msgUser = MsgUser::find($id);
        if (! $msgUser) {
            return $this->errorNotFound();
        }
        return $this->item($msgUser, new MsgUserTransformer());
    }

    function post()
    {
        $validator = \Validator::make($this->request->input(), [
            'name' => 'required|string|max:50',
            'user_id' => 'required|int'
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        MsgUser::create($this->request->all());
        return $this->created();
    }

    function put($id)
    {
        $msgUser = MsgUser::find($id);
        if (! $msgUser) {
            return $this->errorNotFound();
        }
        $msgUser->update($this->request->input());
        return $this->created();
    }

    function delete($id)
    {
        $msgUser = MsgUser::find($id);
        if (! $msgUser) {
            return $this->errorNotFound();
        }
        $msgUser->delete();
        return $this->noContent();
    }
}