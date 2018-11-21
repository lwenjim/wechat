<?php

namespace App\Transformers;

use App\Models\AppCode;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class AppCodeTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['users'];

    public function transform(AppCode $appcode)
    {
        return $appcode->attributesToArray();
    }

    public function includeUsers(AppCode $appcode, ParamBag $params = null)
    {
        if ($params->get('limit')) {
            list($row, $offset) = $params->get('limit');
        } else {
            list($row, $offset) = [20, 0];
        }
        $users = $appcode->users()->orderBy('created_at', 'desc')->take($row)->skip($offset)->get();
        $total = $appcode->users()->count();
        return $this->collection($users, new AppCodeUserTransformer())->setMeta(['total' => $total]);
    }
}
