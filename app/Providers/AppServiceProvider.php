<?php

namespace App\Providers;

use App\Listeners\CatatAutentikasi;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Event::listen(Login::class, [CatatAutentikasi::class, 'masuk']);
        Event::listen(Logout::class, [CatatAutentikasi::class, 'keluar']);
    }
}
