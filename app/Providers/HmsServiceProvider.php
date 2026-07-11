<?php

namespace App\Providers;

use App\Infrastructure\HMS\Contracts\HmsAdapterInterface;
use App\Infrastructure\HMS\Mock\MockHmsAdapter;
use Illuminate\Support\ServiceProvider;

class HmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HmsAdapterInterface::class, MockHmsAdapter::class);
    }
}
