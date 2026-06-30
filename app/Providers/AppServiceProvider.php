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
        app()->setLocale('id');
        \Carbon\Carbon::setLocale('id');

        if (env('APP_ENV') !== 'local' && str_starts_with(env('APP_URL', 'http://'), 'https')) {
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

            // Prevent logout when mocking time by setting massive session lifetime
            // and setting expire_on_close to true (cookie expires on browser close, no absolute timestamp)
            config([
                'session.lifetime' => 5256000, // 10 years
                'session.expire_on_close' => true,
            ]);
        }
    }
}
