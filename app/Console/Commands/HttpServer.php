<?php

namespace App\Console\Commands;

use ErrorException;
use Illuminate\Console\Command;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Swoole\Http\Server;

class HttpServer extends Command
{
    protected $app;
    protected $pid;
    protected $host = '0.0.0.0';
    protected $port = 9503;
    protected $server;
    /**
     * 命令名称
     * @var string
     */
    protected $signature = 'http {action} {--host=} {--port=}';
    /**
     * 命令描述
     * @var string
     */
    protected $description = "示例：
                              启动：php artisan http start --host=127.0.0.1 --port=8080
                              停止：php artisan http stop
                              重启：php artisan http restart
                              重载：php artisan http reload";

    /**
     * 实例化命令
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 执行命令
     * @return mixed
     */
    public function handle()
    {
        $action = strtolower($this->argument('action'));
        $host = $this->option('host');
        $port = $this->option('port');
        $host && $this->host = $host;
        $port && $this->port = $port;
        $this->pid = storage_path('http.pid');
        $this->server = new Server($this->host, $this->port);
        $this->server->set([
            'pid_file' => $this->pid,
            'daemonize' => 0
        ]);
        if (in_array($action, ['start', 'stop', 'restart', 'reload'])) {
            call_user_func([$this, $action]);
        } else {
            $this->error('error action!');
        }
    }

    /**
     * 启动服务
     */
    private function start()
    {
        $this->info(date('Y-m-d H:i:s') . ' Server is starting...' . '[' . $this->host . ':' . $this->port . ']');
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->start();
    }

    public function onWorkerStart()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $this->app = clone (require base_path() . '/bootstrap/app.php');
    }

    public function onRequest($request, $response)
    {
        try {
            $application = $this->app;
            // reflections
            $reflection = new \ReflectionObject($application);
            // dispatch
            $dispatch = $reflection->getMethod('dispatch');
            $illuminateRequest = $this->handRequest($request);
            $illuminateResponse = $dispatch->invoke($application, $illuminateRequest);
            // terminateMiddleware
            $middleware = $reflection->getProperty('middleware');
            $middleware->setAccessible(true);
            if (count($middleware->getValue($application)) > 0) {
                $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
                $callTerminableMiddleware->setAccessible(true);
                $callTerminableMiddleware->invoke($application, $illuminateResponse);
            }
            // Is gzip enabled and the client accept it?
            $accept_gzip = isset($request->header['accept-encoding']) && stripos($request->header['accept-encoding'], 'gzip') !== false;
            if ($illuminateResponse instanceof IlluminateResponse) {
                $this->handResponse($response, $illuminateResponse, $accept_gzip);
            } else {
                $response->end((string)$illuminateResponse);
            }
            $application = null;
            //$request = null;
            //$response = null;
            $illuminateRequest = null;
            $illuminateResponse = null;
        } catch (ErrorException $e) {
            $this->error($e->getFile() . '(' . $e->getLine() . '): ' . $e->getMessage());
        }
    }

    private function handRequest($request)
    {
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $files = isset($request->files) ? $request->files : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $header = isset($request->header) ? $request->header : [];
        $server = isset($request->server) ? $request->server : [];
        $content = $request->rawContent();

        foreach ($server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }
        foreach ($header as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (!in_array($key, ['REMOTE_ADDR', 'SERVER_PORT', 'HTTPS'])) {
                $key = 'HTTP_' . $key;
            }
            $_SERVER[$key] = $value;
        }
        $server = $_SERVER;

        IlluminateRequest::enableHttpMethodParameterOverride();

        if ('cli-server' === PHP_SAPI) {
            if (array_key_exists('HTTP_CONTENT_LENGTH', $server)) {
                $server['CONTENT_LENGTH'] = $server['HTTP_CONTENT_LENGTH'];
            }
            if (array_key_exists('HTTP_CONTENT_TYPE', $server)) {
                $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
            }
        }

        $request = new IlluminateRequest($get, $post, [], $cookie, $files, $server, $content);

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return IlluminateRequest::createFromBase($request);
    }

    private function handResponse($response, $illuminateResponse, $accept_gzip)
    {
        if (!$illuminateResponse->headers->has('Date')) {
            $illuminateResponse->setDate(\DateTime::createFromFormat('U', time()));
        }
        // headers
        foreach ($illuminateResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }
        // status
        $response->status($illuminateResponse->status());
        // cookies
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $method = $cookie->isRaw() ? 'rawcookie' : 'cookie';
            $response->$method(
                $cookie->getName(), $cookie->getValue(),
                $cookie->getExpiresTime(), $cookie->getPath(),
                $cookie->getDomain(), $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        // check gzip
        if ($accept_gzip && isset($response->header['Content-Type'])) {
            $response->gzip(1);
        }
        // content
        if ($illuminateResponse instanceof BinaryFileResponse) {
            $response->sendfile($illuminateResponse->getFile()->getPathname());
        } else {
            // send content & close
            $response->end($illuminateResponse->content());
        }
    }

    /**
     * 停止服务
     */
    private function stop()
    {
        $this->info(date('Y-m-d H:i:s') . ' Server is stopping...');
        $pid = $this->getPid();
        posix_kill($pid, SIGTERM);
        usleep(500);
        posix_kill($pid, SIGKILL);
        unlink($this->pid);
    }

    /**
     * 重载服务
     */
    private function reload()
    {
        $this->info(date('Y-m-d H:i:s') . ' Server is reloading...');
        posix_kill($this->getPid(), SIGUSR1);
    }

    /**
     * 重启服务
     */
    private function restart()
    {
        $this->info(date('Y-m-d H:i:s') . ' Server is stopping...');
        $pid = $this->getPid();
        $cmd = exec("ps -p {$pid} -o args | grep swoole-server");
        if (empty($cmd)) {
            throw new \Exception('Cannot find server process.');
        }
        $this->stop();
        usleep(2000);
        $this->info(date('Y-m-d H:i:s') . ' Server is starting...');
        exec($cmd);
    }

    /**
     * 获取进程id
     */
    private function getPid()
    {
        if (!file_exists($this->pid)) {
            throw new \Exception('The Server is not running.');
        }
        $pid = file_get_contents($this->pid);
        if (posix_getpgid($pid)) {
            return $pid;
        }
        unlink($this->pid);
        return false;
    }
}