<?php

namespace App\Http\Controllers;

use App\Transformers\UserAddressTransformer;
use App\Models\UserAddress;

class UserAddressController extends Controller
{
    public function list()
    {
        $UserAddress = UserAddress::where('user_id', $this->user()->switchToMiniUser()->id)->orderBy('id', 'desc')->get();
        return $this->collection($UserAddress, new UserAddressTransformer());
    }

    public function get($id)
    {
        $userAddress = UserAddress::where(['id' => $id, 'user_id' => $this->user()->switchToMiniUser()->id])->first();
        if (!$userAddress) {
            return $this->errorNotFound();
        }
        return $this->item($userAddress, new UserAddressTransformer());
    }

    public function post()
    {
        $user = $this->user()->switchToMiniUser();
        $data = $this->request->input();
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:20',
            'mobile' => 'required|int',
            'province' => 'required|string|max:20',
            'city' => 'required|string|max:20',
            'district' => 'required|string|max:20',
            'address' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        if ($this->request->input('default') == 1) {
            UserAddress::where(['user_id' => $user->id, 'default' => 1])->update(['default' => 0]);
        }
        $data['user_id'] = $user->id;
        UserAddress::create($data);
        return $this->created();
    }

    public function put($id)
    {
        $userAddress = UserAddress::find($id);
        if (!$userAddress) {
            return $this->errorNotFound();
        }
        if ($this->request->input('default') == 1) {
            UserAddress::where(['user_id' => $this->user()->switchToMiniUser()->id, 'default' => 1])->update(['default' => 0]);
        }
        $userAddress->update($this->request->input());
        return $this->created();
    }

    public function delete($id)
    {
        $userAddress = UserAddress::where(['id' => $id, 'user_id' => $this->user()->switchToMiniUser()->id])->first();
        if (!$userAddress) {
            return $this->errorNotFound();
        }
        $userAddress->delete();
        return $this->noContent();
    }
}