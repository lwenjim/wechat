<?php

namespace App\Console\Commands;

use App\Jobs\Sign;
use App\Models\MsgSession;
use App\Models\User;
use App\Models\UserSign;
use App\Models\UserSignComment;
use App\Models\WeChatQrcodeUser;
use App\Models\WechatUser;
use function foo\func;
use function getApp;
use Illuminate\Console\Command;
use DB;
use Mockery\Exception;
use function print_r;
use App\Console\Kernel;
use const true;
use Intervention\Image\ImageManagerStatic as Image;

use App\Models\Wechat;
use App\Models\Achievement;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Core\Exceptions\HttpException;
use App\Console\Commands\Shell\batchBuka;

class TestServer extends Command
{
    protected $signature = 'test';
    protected $description = 'Command description';

    public function handle()
    {
        batchBuka::run();
    }
}