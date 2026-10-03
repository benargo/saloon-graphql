<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Tests\Fixtures;

use Saloon\Http\Connector;

class PlainConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://graphql.test';
    }
}
