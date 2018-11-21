<?php

namespace App\Transformers;

use App\Models\TokenUsers;
use League\Fractal\TransformerAbstract;

class TokenUserTransformer extends TransformerAbstract
{
    public function transform(TokenUsers $TokenUsers)
    {
        return $TokenUsers->attributesToArray();
    }
}
