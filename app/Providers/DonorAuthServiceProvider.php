<?php

namespace App\Providers;

use App\Services\DonorSessionStore;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class DonorAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DonorSessionStore::class);
    }

    public function boot(): void
    {
        Auth::extend('donor-session', function ($app, string $name, array $config): SessionGuard {
            $guard = new SessionGuard(
                $name,
                Auth::createUserProvider($config['provider']),
                $app->make(DonorSessionStore::class)->session,
                rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
                timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
                hashKey: $app['config']->get('app.key'),
            );
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
