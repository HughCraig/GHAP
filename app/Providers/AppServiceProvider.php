<?php

namespace TLCMap\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Previously was EMPTY
        $this->app->bind('path.public', function () {
            return base_path() . '/public/ghap';
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::if ('admin', function () {
            if (auth()->user() && auth()->user()->hasAnyRole(['ADMIN', 'SUPER_ADMIN'])) {
                return 1;
            }
            return 0;
        });

        Blade::if ('superadmin', function () {
            if (auth()->user() && auth()->user()->hasRole('SUPER_ADMIN')) {
                return 1;
            }
            return 0;
        });
    }
}
