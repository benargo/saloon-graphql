<?php

declare(strict_types=1);

use Saloon\Exceptions\Request\RequestException;
use Saloon\GraphQL\Exceptions\GraphQLException;

$errors = [
    ['message' => 'Field "foo" not found', 'locations' => [['line' => 1, 'column' => 3]], 'path' => ['foo']],
    ['message' => 'Rate limit exceeded', 'extensions' => ['code' => 'RATE_LIMITED']],
];

it('returns the full error list', function () use ($errors): void {
    expect((new GraphQLException(graphQLResponse(['errors' => $errors])))->getErrors())->toBe($errors);
});

it('returns the first error message', function () use ($errors): void {
    expect((new GraphQLException(graphQLResponse(['errors' => $errors])))->getFirstError())->toBe('Field "foo" not found');
});

it('returns null for the first error when there are none', function (): void {
    expect((new GraphQLException(graphQLResponse(['errors' => []])))->getFirstError())->toBeNull();
});

it('matches error messages by regex', function () use ($errors): void {
    $exception = new GraphQLException(graphQLResponse(['errors' => $errors]));

    expect($exception->hasErrorMatching('/rate limit/i'))->toBeTrue()
        ->and($exception->hasErrorMatching('/permission denied/i'))->toBeFalse();
});

it('defaults the message to the first error', function () use ($errors): void {
    expect((new GraphQLException(graphQLResponse(['errors' => $errors])))->getMessage())
        ->toBe('GraphQL request failed: Field "foo" not found');
});

it('falls back to an unknown error message', function (): void {
    expect((new GraphQLException(graphQLResponse(['errors' => []])))->getMessage())
        ->toBe('GraphQL request failed: Unknown error');
});

it('keeps an explicit message', function () use ($errors): void {
    expect((new GraphQLException(graphQLResponse(['errors' => $errors]), 'Custom'))->getMessage())->toBe('Custom');
});

it('is a Saloon request exception exposing the response', function () use ($errors): void {
    $response = graphQLResponse(['errors' => $errors]);
    $exception = new GraphQLException($response);

    expect($exception)->toBeInstanceOf(RequestException::class)
        ->and($exception->getResponse())->toBe($response);
});

it('tolerates errors without a string message', function (): void {
    $exception = new GraphQLException(graphQLResponse(['errors' => [['extensions' => []], ['message' => 5]]]));

    expect($exception->getFirstError())->toBeNull()
        ->and($exception->hasErrorMatching('/.*/'))->toBeFalse()
        ->and($exception->getMessage())->toBe('GraphQL request failed: Unknown error');
});

it('has no errors when the body is not a JSON object', function (string $body, array $headers): void {
    expect((new GraphQLException(graphQLResponse($body, 500, $headers)))->getErrors())->toBe([]);
})->with([
    'malformed json' => ['{"errors": [', ['Content-Type' => 'application/json']],
    'html' => ['<html>Bad Gateway</html>', ['Content-Type' => 'text/html']],
    'json null' => ['null', ['Content-Type' => 'application/json']],
    'json scalar' => ['"oops"', ['Content-Type' => 'application/json']],
]);
