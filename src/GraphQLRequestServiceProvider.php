<?php

declare(strict_types=1);

namespace Saloon\GraphQLRequest;

use Illuminate\Support\ServiceProvider;

class GraphQLRequestServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GraphQLRequest::class);
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
