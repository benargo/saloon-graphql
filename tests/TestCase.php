<?php

declare(strict_types=1);

namespace Saloon\GraphQLRequest\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\GraphQLRequest\GraphQLRequestServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GraphQLRequestServiceProvider::class,
        ];
    }
}
