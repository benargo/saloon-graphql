<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Tests\Fixtures;

use Saloon\GraphQL\Traits\HandlesGraphQLErrors;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class TestConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use HandlesGraphQLErrors;

    public function resolveBaseUrl(): string
    {
        return 'https://graphql.test';
    }
}
