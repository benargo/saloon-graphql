<?php

declare(strict_types=1);

use Saloon\GraphQL\GraphQLRequestServiceProvider;

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('Saloon\GraphQL')
    ->toUseStrictTypes();

arch('the package source is framework-agnostic')
    ->expect('Saloon\GraphQL')
    ->not->toUse('Illuminate')
    ->ignoring(GraphQLRequestServiceProvider::class);

arch('the package source uses no Laravel helpers')
    ->expect(['blank', 'filled', 'collect', 'data_get', 'value', 'tap', 'app', 'config'])
    ->not->toBeUsedIn('Saloon\GraphQL');
