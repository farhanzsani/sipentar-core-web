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
        // Bind DiagnosisServiceInterface to stub implementation
        // Switch to DiagnosisHttpService when Python CV service is deployed
        $this->app->bind(
            \App\Services\Contracts\DiagnosisServiceInterface::class,
            \App\Services\DiagnosisStubService::class
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
