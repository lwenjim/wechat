<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wechat;
use App\Models\WechatUser;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Mockery\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Models\UserOpenidCorrelationMini;

class Controller extends BaseController
{
    protected $manager = null;
    protected $request = null;

    function __construct(Request $request)
    {
        $this->manager = new Manager();
        $this->request = $request;
        if ($this->request->has('include')) {
            $this->manager->parseIncludes($this->request->get('include'));
        }
    }

    public function created($location = null, $content = null)
    {
        $response = new Response($content);
        $response->setStatusCode(201);

        if (!is_null($location)) {
            $response->header('Location', $location);
        }

        return $response;
    }

    public function accepted($location = null, $content = null)
    {
        $response = new Response($content);
        $response->setStatusCode(202);

        if (!is_null($location)) {
            $response->header('Location', $location);
        }

        return $response;
    }

    public function noContent()
    {
        $response = new Response(null);

        return $response->setStatusCode(204);
    }

    public function collection($collection, $transformer)
    {
        $resource = new Collection($collection, $transformer);
        return $this->manager->createData($resource)->toArray();
    }

    public function item($item, $transformer)
    {
        $resource = new Item($item, $transformer);
        return $this->manager->createData($resource)->toArray();
    }

    public function paginator($paginator, $transformer)
    {
        $collections = $paginator->getCollection();
        $resource = new Collection($collections, $transformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        return $this->manager->createData($resource)->toArray();
    }

    public function error($message, $statusCode)
    {
        throw new HttpException($statusCode, $message);
    }

    public function errorNotFound($message = 'Not Found')
    {
        $this->error($message, 404);
    }

    public function errorBadRequest($message = 'Bad Request')
    {
        $this->error($message, 400);
    }

    public function errorForbidden($message = 'Forbidden')
    {
        $this->error($message, 403);
    }

    public function errorInternal($message = 'Internal Error')
    {
        $this->error($message, 500);
    }

    public function errorUnauthorized($message = 'Unauthorized')
    {
        $this->error($message, 401);
    }

    public function errorMethodNotAllowed($message = 'Method Not Allowed')
    {
        $this->error($message, 405);
    }

    protected function user()
    {
//        return \App\Models\User::where('id', 3457645)->first();
        return \Auth::user();
    }

    protected function webUser($uid = 0)
    {
        $user = $uid ? \App\Models\User::where('id', $uid)->first() : $this->user();
        if ($user->id > 0) {
            return $user->switchToMiniUser();
        }
        if ($user->is_mini_user == 1) {
            return $user;
        }
        info($user->openid . '还未绑定到中间者');
        throw  new Exception('operate webUser failed');
    }

    protected function http($url, $data = [])
    {
        $client = new \GuzzleHttp\Client();
        if ($data) {
            $response = $client->request('POST', $url, ['form_params' => $data]);
            return json_decode($response->getBody(), true);
        } else {
            return json_decode($client->get($url)->getBody(), true);
        }
    }

    protected function bindGzhToMini($user = null)
    {
        $user = $user ?: $this->user();
        $appid = $this->request->input('appid');
        $openid = $this->request->input('openid');
//        $openid = $openid ?: $this->getGzhOpenidByAppid($appid);
        if (empty($appid) || empty($openid)) return;
        $wechat = Wechat::where(['appid' => $appid, 'status' => 1])->first();
        $user->update(['last_appid' => $appid, 'last_openid' => $openid, 'config->remind' => $wechat->id]);
        $gzhUser = User::where(['openid' => $openid])->first();
        if (!empty($gzhUser)) {
            $gzhUser->update(['mini_user_id' => $user->id]);
        }
    }

    protected function getGzhOpenidByAppid($appid)
    {
        return UserOpenidCorrelationMini::where(['appid' => $appid, 'mini_openid' => $this->user()->openid])->value('gzh_openid');
    }

    public function joinTagUser($app, $openid)
    {
        $tag = $app->user_tag;
        $tagId = getTagIdFordoesntExistCustomMenu($tag);
        $openIds = [$openid];
        $tag->batchTagUsers($openIds, $tagId);
    }

    public function enableGzh($wechat, $openid)
    {
        if (empty($wechat)) return;
        $redis = app('redis');
        $redis->setex('mornight:active:' . $wechat->appid . ':' . $openid, 172800, date('Y-m-d H:i:s'));
        $gzhUser = User::where('openid', $openid)->first();
        if (empty($gzhUser) || $gzhUser->mini_user_id <= 0) return false;
        $gzhUser->switchToMiniUser()->update(['config->remind' => $wechat->id, 'last_appid' => $wechat->appid, 'last_openid' => $openid]);
    }

    public function getUserIds()
    {
        static $userIds = [];
        $appid = $this->request->input('appid') ?: $this->user()->switchToMiniUser()->last_appid ?: false;
        if (!empty($userIds) || !$appid) return $userIds;
        $redisKey = 'tmp:user2:' . $appid;
        $redis = app('redis');
        if (empty($userid = $redis->get($redisKey))) {
            $userid = User::where(['last_appid' => $appid, 'is_mini_user' => 1])->pluck('id')->toArray();
            $redis->setex($redisKey, 3600, implode(',', $userid));
        } else {
            $userid = explode(',', $userid);
        }
        return $userid;
    }

    public function getLastAppid()
    {
        $gzhUser = $this->user();
        $miniUser = $gzhUser->switchToMiniUser();
        $lastAppid = $this->request->input('appid') ?: $miniUser->last_appid ?: false;
        if ($lastAppid) return $lastAppid;
        $wechatUser = WechatUser::where(['openid' => $gzhUser->openid])->first();
        if (empty($wechatUser)) return false;
        return $wechatUser->wechat()->value('appid');
    }
}