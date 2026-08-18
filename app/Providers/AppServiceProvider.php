<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // Ҳангоми ворид шудан — токени ягонаи сессия сохта мешавад
        Event::listen(Login::class, function (Login $event) {
            $token = Str::random(40);
            $event->user->forceFill(['session_token' => $token])->save();
            session()->put('single_session_token', $token);
        });
    }
}
