<?php

namespace App\Services\Wechat;

use EasyWeChat\Foundation\Application as EasyWeChatApplication;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Boot the provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->configure('wechat');
    }

    /**
     * Register the provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EasyWeChatApplication::class, function ($laravelApp) {
            $app = new EasyWeChatApplication(config('wechat'));
            if (config('wechat.use_laravel_cache')) {
                $app->cache = new CacheBridge();
            }
            $app->server->setRequest($laravelApp['request']);

            return $app;
        });

        $this->app->alias(EasyWeChatApplication::class, 'wechat');
        $this->app->alias(EasyWeChatApplication::class, 'easywechat');
    }
}
