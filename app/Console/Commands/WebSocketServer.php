<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Swoole\WebSocket\Server;
use Swoole\Redis;

class WebSocketServer extends Command
{
    protected $signature = 'websocket:start';
    protected $description = 'Swoole WebSocket Server';
    protected $server = null;
    protected $redis = null;
    protected $client = null;

    public function onOpen($server, $request)
    {
        $this->client->hset('wechatmsgclient', $request->fd, '[' . date('Y-m-d H:i:s') . '] IP:' . $request->server['remote_addr'] . ' UA:' . $request->header['user-agent']);
    }

    public static function getRedisInfo()
    {
        static $config;
        if ($config) return $config;
        $env = file_get_contents($filename = app()->basePath() . '/.env');
        preg_match("/REDIS_HOST=(.*)/", $env, $match);
        $host = trim($match[1]);

        preg_match("/REDIS_PASSWORD=(.*)/", $env, $match);
        $passwd = trim($match[1]);

        preg_match("/REDIS_PORT=(.*)/", $env, $match);
        $port = trim($match[1]);

        return $config = [$host, $port, $passwd];
    }

    public function onMessage($server, $frame)
    {
        if ($this->redis) return;
        $this->redis = new Redis(['timeout' => -1, 'password' => static::getRedisInfo()[2]]);
        $this->redis->on('message', function (Redis $redis, $result) use ($server) {
            list($type, $channel, $message) = $result;
            if ($type != 'message' || $channel != 'wechatmsg') return;
            $clients = $this->client->hkeys('wechatmsgclient');
            foreach ($clients as $fd) $server->push($fd, $message);
        });
        $this->redis->connect(static::getRedisInfo()[0], static::getRedisInfo()[1], function (Redis $redis, $result) {
            $redis->subscribe('wechatmsg');
        });
    }

    public function onClose($server, $fd)
    {
        $this->client->hdel('wechatmsgclient', $fd);
    }

    public function handle()
    {
        $this->client = app('redis');
        $this->server = new Server('0.0.0.0', 9503);
        $this->server->set([
            'worker_num' => 1,
            'daemonize' => true,
        ]);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->start();
    }
}