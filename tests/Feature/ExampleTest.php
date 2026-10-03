<?php

declare(strict_types=1);

use Saloon\GraphQL\GraphQLRequest;

it('resolves the singleton', function () {
    expect(app(GraphQLRequest::class))->toBeInstanceOf(GraphQLRequest::class);
});

it('returns the same instance from the container', function () {
    expect(app(GraphQLRequest::class))->toBe(app(GraphQLRequest::class));
});
