<?php

namespace App\Providers;

use Livewire\Blaze\Blaze;
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
        Blaze::optimize()->in(
            resource_path('views/components'),
            fold: true
        );
    }
}
