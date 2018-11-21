<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    function __construct(Request $request)
    {
        parent::__construct($request);
    }

    function getPrize($_prize)
    {
        $prize = '';
        $sum = array_sum($_prize);
        foreach ($_prize as $key => $value) {
            $num = mt_rand(1, $sum);
            if ($num <= $value) {
                $prize = $key;
                break;
            } else {
                $sum -= $value;
            }
        }
        return $prize;
    }
}