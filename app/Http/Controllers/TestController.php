<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WechatUser;
use function array_intersect_key;
use function get_headers;
use function getApp;
use Mockery\Exception;
use OSS\OssClient;
use OSS\Core\OssException;
class TestController extends Controller
{
    public function test()
    {
    }
}