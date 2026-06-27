<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') !== 'local') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if (app()->environment(['local', 'development']) 
            && \Illuminate\Support\Facades\Schema::hasTable('cache') 
            && \Illuminate\Support\Facades\Cache::has('time_offset')
        ) {
            $offset = \Illuminate\Support\Facades\Cache::get('time_offset');
            \Carbon\Carbon::setTestNow(function () use ($offset) {
                return \Carbon\Carbon::parse(date('Y-m-d H:i:s', time() + $offset));
            });
        }
    }
}
