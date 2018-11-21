<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \DB::listen(function ($query) {
            $sql = $query->sql;
            $bingings = $query->bindings;
            $time = $query->time;
            if ($time > 500) {
                $request = array_only($_SERVER, ['REQUEST_URI', 'REQUEST_METHOD', 'QUERY_STRING']);
//                app('redis')->lpush('debug_slow_sql', print_r(compact('sql', 'bingings', 'time', 'request'), 1));
            }
        });
    }
}
