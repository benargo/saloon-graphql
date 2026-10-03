<?php

declare(strict_types=1);

namespace Saloon\GraphQL\Exceptions;

use InvalidArgumentException;
use JsonException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Throwable;
use WeakMap;

class GraphQLException extends RequestException
{
    /**
     * The raw GraphQL errors: each one has a message and may have
     * locations, path and extensions.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $errors;

    /**
     * Parsed errors per response, so each body is decoded only once.
     *
     * @var WeakMap<object, array<int, array<string, mixed>>>|null
     */
    private static ?WeakMap $cache = null;

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
        self::$cache ??= new WeakMap;

        return self::$cache[$response] ??= self::parseErrors($response);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function parseErrors(Response $response): array
    {
        // Decode the raw body rather than calling $response->json(), which
        // throws a TypeError when the JSON is a scalar or null.
        try {
            $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($body) || ! is_array($body['errors'] ?? null) || ! array_is_list($body['errors'])) {
            return [];
        }

        // Every entry is kept, so a non-empty list always counts as a failure.
        // Non-compliant servers sometimes send bare strings as errors.
        return array_map(
            static fn (mixed $error): array => match (true) {
                is_array($error) => $error,
                is_string($error) => ['message' => $error],
                default => [],
            },
            $body['errors'],
        );
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

    /**
     * @throws InvalidArgumentException If the pattern is not a valid regular expression.
     */
    public function hasErrorMatching(string $pattern): bool
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException(sprintf('Invalid regular expression: %s', $pattern));
        }

        foreach ($this->errors as $error) {
            if (is_string($error['message'] ?? null) && preg_match($pattern, $error['message']) === 1) {
                return true;
            }
        }

        return false;
    }
}
