<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);

$app->withFacades();
$app->withEloquent();
$app->configure('app');
$app->configure('auth');
$app->configure('process');
$app->configure('database');
$app->configure('filesystems');
$app->configure('config');
$app->middleware([
    App\Http\Middleware\Sql::class
]);
$app->routeMiddleware([
    'authToken' => App\Http\Middleware\AuthToken::class
]);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Services\Wechat\ServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
$app->register(Askedio\SoftCascade\Providers\LumenServiceProvider::class);
$app->configureMonologUsing(function (Monolog\Logger $monoLog) use ($app) {
    $env = file_get_contents(__DIR__ . '/../.env');
    preg_match("/REDIS_HOST=(.*)/", $env, $match);
    $host = trim($match[1]);

    preg_match("/REDIS_PASSWORD=(.*)/", $env, $match);
    $passwd = trim($match[1]);

    preg_match("/REDIS_PORT=(.*)/", $env, $match);
    $port = trim($match[1]);

    if (!empty($passwd) && strtolower($passwd) != 'null') {
        $options = ['parameters' => ['password' => $passwd]];
        $Client = new Predis\Client("tcp://{$host}:{$port}",$options);
    }else{
        $Client = new Predis\Client("tcp://{$host}:{$port}");
    }
    return $monoLog->pushHandler((new Monolog\Handler\RedisHandler($Client, "mornight.logs", "prod"))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true)))->pushProcessor(new Monolog\Processor\WebProcessor())->pushProcessor(new Monolog\Processor\IntrospectionProcessor());
});

require __DIR__ . '/../app/Http/routes.php';

return $app;
