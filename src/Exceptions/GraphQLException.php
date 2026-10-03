<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Exceptions;

use JsonException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Throwable;

class GraphQLException extends RequestException
{
    /**
     * The raw GraphQL errors: each one has a message and may have
     * locations, path and extensions.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $errors;

    public function __construct(Response $response, ?string $message = null, int $code = 0, ?Throwable $previous = null)
    {
        $this->errors = self::errorsFrom($response);

        parent::__construct(
            $response,
            $message ?? sprintf('GraphQL request failed: %s', $this->getFirstError() ?? 'Unknown error'),
            $code,
            $previous,
        );
    }

    /**
     * Extract the GraphQL error list from a response body.
     *
     * @internal
     *
     * @return array<int, array<string, mixed>>
     */
    public static function errorsFrom(Response $response): array
    {
        // Decode the raw body rather than calling $response->json(), which
        // throws a TypeError when the JSON is a scalar or null.
        try {
            $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($body) || ! is_array($body['errors'] ?? null)) {
            return [];
        }

        return array_values(array_filter($body['errors'], is_array(...)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        $message = $this->errors[0]['message'] ?? null;

        return is_string($message) ? $message : null;
    }

    public function hasErrorMatching(string $pattern): bool
    {
        foreach ($this->errors as $error) {
            if (is_string($error['message'] ?? null) && preg_match($pattern, $error['message']) === 1) {
                return true;
            }
        }

        return false;
    }
}
