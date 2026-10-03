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
     * Returns null for non-GraphQL failures and for error HTTP statuses, so
     * Saloon's status-specific exceptions (401, 429, 503 and so on) still apply.
     */
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        if ($response->status() >= 400) {
            return null;
        }

        return $this->hasGraphQLErrors($response)
            ? $this->createGraphQLException($response, $senderException)
            : null;
    }

    /**
     * Decide whether a response carrying GraphQL errors is a failure. Override
     * this to accept partial results, for example by returning false when the
     * response also has usable data.
     */
    protected function shouldTreatGraphQLErrorsAsFailure(Response $response): bool
    {
        return true;
    }

    protected function createGraphQLException(Response $response, ?Throwable $senderException): GraphQLException
    {
        return new GraphQLException($response, previous: $senderException);
    }

    private function hasGraphQLErrors(Response $response): bool
    {
        return $response->isJson()
            && GraphQLException::errorsFrom($response) !== []
            && $this->shouldTreatGraphQLErrorsAsFailure($response);
    }
}
