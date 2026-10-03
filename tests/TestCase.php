<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\GraphQL\GraphQLRequestServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GraphQLRequestServiceProvider::class,
        ];
    }
}
