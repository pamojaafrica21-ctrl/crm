<?php

namespace App\Providers;

use App\Domain\Properties\Services\PropertyContext;
use App\Infrastructure\Payments\PaymentGatewayManager;
use App\Infrastructure\Sms\NullSmsChannel;
use App\Infrastructure\Sms\SmsChannelInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PropertyContext::class);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->bind(SmsChannelInterface::class, NullSmsChannel::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user instanceof \App\Models\User && $user->hasRole('Administrator')) {
                return true;
            }
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof \App\Models\User) {
                activity()
                    ->causedBy($event->user)
                    ->log('User logged in');
            }
        });
    }
}
