<?php

declare(strict_types=1);

use Saloon\GraphQL\GraphQLRequestServiceProvider;

it('registers the service provider in a Laravel application', function (): void {
    expect(app()->getProviders(GraphQLRequestServiceProvider::class))->not->toBeEmpty();
});
