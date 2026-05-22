<?php

namespace App\Providers;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Listeners\SendWebPushOnNotification;

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
        if (env('APP_ENV') !== 'local' || str_contains(env('APP_URL'), 'https')) {
            URL::forceScheme('https');
        }
        
        Vite::prefetch(concurrency: 3);

        // Kirim Web Push setiap kali SystemNotification dikirim via database
        Event::listen(NotificationSent::class, SendWebPushOnNotification::class);
    }
}
