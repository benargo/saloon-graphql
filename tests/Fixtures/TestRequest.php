<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Tests\Fixtures;

use Saloon\GraphQL\GraphQLRequest;

class TestRequest extends GraphQLRequest
{
    public const string QUERY = 'query Thing($id: Int!) { thing(id: $id) { id } }';

    public function resolveEndpoint(): string
    {
        return '/graphql';
    }

    protected function graphQLQuery(): string
    {
        return self::QUERY;
    }

    /**
     * @return array<string, mixed>
     */
    protected function variables(): array
    {
        return ['id' => 1];
    }
}
