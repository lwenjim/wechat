<?php

namespace App\Http\Controllers\Admin;

use App\Transformers\UserFeedbackTransformer;
use App\Models\UserFeedback;

class UserFeedbackController extends AdminController
{
    function index()
    {
        $userFeedback = UserFeedback::orderBy('id', 'desc')->paginate();
        return $this->paginator($userFeedback, new UserFeedbackTransformer());
    }

    function get($id)
    {
        $userFeedback = UserFeedback::find($id);
        if (!$userFeedback) {
            return $this->errorNotFound();
        }
        return $this->item($userFeedback, new UserFeedbackTransformer());
    }

    function put($id)
    {
        $userFeedback = UserFeedback::find($id);
        if (!$userFeedback) {
            return $this->errorNotFound();
        }
        $userFeedback->update($this->request->input());
        return $this->created();
    }

    function delete($id)
    {
        $UserFeedback = UserFeedback::find($id);
        if (!$UserFeedback) {
            return $this->errorNotFound();
        }
        $UserFeedback->delete();
        return $this->noContent();
    }
}