<?php

declare(strict_types=1);

namespace Saloon\GraphQL;

use Illuminate\Support\ServiceProvider;

class GraphQLRequestServiceProvider extends ServiceProvider
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
        if (! $this->app->runningInConsole()) {
            return;
        }
    }
}
