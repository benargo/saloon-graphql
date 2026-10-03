<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Traits;

use Saloon\GraphQL\Exceptions\GraphQLException;
use Saloon\Http\Response;
use Throwable;

trait HandlesGraphQLErrors
{
    /**
     * Returns true for GraphQL errors and null otherwise, never false, so
     * Saloon's status-based failure detection still applies.
     */
    public function hasRequestFailed(Response $response): ?bool
    {
        return $this->hasGraphQLErrors($response) ? true : null;
    }

    /**
     * Returns null for non-GraphQL failures so Saloon's default exceptions apply.
     */
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return $this->hasGraphQLErrors($response)
            ? $this->createGraphQLException($response, $senderException)
            : null;
    }

    protected function createGraphQLException(Response $response, ?Throwable $senderException): GraphQLException
    {
        return new GraphQLException($response, previous: $senderException);
    }

    private function hasGraphQLErrors(Response $response): bool
    {
        return $response->isJson() && GraphQLException::errorsFrom($response) !== [];
    }
}
