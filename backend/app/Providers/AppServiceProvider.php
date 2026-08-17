<?php

namespace App\Providers;

use App\Services\Payments\Interfaces\PaymentProviderInterface;
use App\Services\Payments\LavaTopService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentProviderInterface::class,
            LavaTopService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
