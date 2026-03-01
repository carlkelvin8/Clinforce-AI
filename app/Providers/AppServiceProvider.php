<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use App\Support\NullViewFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(ViewServiceProvider::class);

        if (! $this->app->bound(ViewFactoryContract::class)) {
            $this->app->singleton(ViewFactoryContract::class, fn () => new NullViewFactory());
        }
        if (! $this->app->bound('view')) {
            $this->app->singleton('view', fn () => new NullViewFactory());
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
