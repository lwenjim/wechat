<?php

namespace App\Http\Controllers\Admin;


use App\Models\TokenUsers;
use App\Transformers\TokenUserTransformer;

class UsersTokenController extends AdminController
{
    public function index()
    {
        return $this->paginator(TokenUsers::paginate(),new TokenUserTransformer());
    }
}