<?php

namespace App\Providers;

use App\Listeners\RecordLastLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fail loudly on N+1 queries and on mass-assignment typos while
        // developing and testing, but never take production down for them.
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Fires for the Filament panel and the student login alike.
        Event::listen(Login::class, RecordLastLogin::class);
    }
}
