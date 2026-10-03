<?php

declare(strict_types=1);

use Saloon\Exceptions\Request\ServerException;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Exceptions\Request\Statuses\ServiceUnavailableException;
use Saloon\Exceptions\Request\Statuses\TooManyRequestsException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Saloon\GraphQL\Exceptions\GraphQLException;
use Saloon\GraphQL\Tests\Fixtures\CustomGraphQLException;
use Saloon\GraphQL\Tests\Fixtures\PlainConnector;
use Saloon\GraphQL\Tests\Fixtures\TestConnector;
use Saloon\GraphQL\Tests\Fixtures\TestRequest;
use Saloon\GraphQL\Traits\HandlesGraphQLErrors;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

/**
 * @param  array<string, mixed>|string  $body
 * @param  array<string, string>  $headers
 */
function sendThrough(TestConnector $connector, array|string $body, int $status = 200, array $headers = ['Content-Type' => 'application/json']): Response
{
    return $connector
        ->withMockClient(new MockClient([MockResponse::make($body, $status, $headers)]))
        ->send(new TestRequest);
}

it('throws a GraphQLException for a 200 with errors', function (): void {
    sendThrough(new TestConnector, ['errors' => [['message' => 'Boom']]]);
})->throws(GraphQLException::class, 'GraphQL request failed: Boom');

it('succeeds for a 200 without errors', function (array $body): void {
    expect(sendThrough(new TestConnector, $body)->status())->toBe(200);
})->with([
    'no errors key' => [['data' => ['thing' => ['id' => 1]]]],
    'empty errors' => [['data' => ['thing' => ['id' => 1]], 'errors' => []]],
    'null errors' => [['data' => null, 'errors' => null]],
    'object errors' => [['errors' => ['message' => 'not a list of errors']]],
    'string errors' => [['errors' => 'boom']],
]);

it('throws for a non-empty errors list whose entries are not objects', function (array $errors, string $message): void {
    expect(fn () => sendThrough(new TestConnector, ['errors' => $errors]))
        ->toThrow(GraphQLException::class, $message);
})->with([
    'string entry' => [['Not authorised'], 'GraphQL request failed: Not authorised'],
    'null entry' => [[null], 'GraphQL request failed: Unknown error'],
]);

it('does not treat a keyed errors map as a GraphQL failure', function (): void {
    expect(sendThrough(new TestConnector, ['errors' => ['a' => ['message' => 'x']]])->status())->toBe(200);
});

it('throws for a partial result with data and errors', function (): void {
    sendThrough(new TestConnector, ['data' => ['thing' => null], 'errors' => [['message' => 'Partial']]]);
})->throws(GraphQLException::class);

it('leaves a 500 without errors to Saloon', function (): void {
    sendThrough(new TestConnector, ['data' => null], 500);
})->throws(InternalServerErrorException::class);

it('leaves a 502 with an HTML body to Saloon', function (): void {
    sendThrough(new TestConnector, '<html>Bad Gateway</html>', 502, ['Content-Type' => 'text/html']);
})->throws(ServerException::class);

it('does not throw JsonException for a malformed JSON body', function (): void {
    expect(sendThrough(new TestConnector, '{"errors": [')->status())->toBe(200);
});

it('leaves a 500 with a malformed JSON body to Saloon', function (): void {
    sendThrough(new TestConnector, '{"errors": [', 500);
})->throws(InternalServerErrorException::class);

it('ignores JSON bodies that are not objects', function (string $body): void {
    expect(sendThrough(new TestConnector, $body)->status())->toBe(200);
})->with(['null', '"oops"', '42']);

it('recognises GraphQL-specific JSON content types', function (): void {
    sendThrough(
        new TestConnector,
        ['errors' => [['message' => 'Boom']]],
        headers: ['Content-Type' => 'application/graphql-response+json; charset=utf-8'],
    );
})->throws(GraphQLException::class);

it('ignores errors when the response is not JSON', function (): void {
    expect(sendThrough(new TestConnector, '{"errors":[{"message":"Boom"}]}', headers: ['Content-Type' => 'text/plain'])->status())->toBe(200);
});

it('returns null rather than false when there are no GraphQL errors', function (): void {
    $connector = new TestConnector;

    expect($connector->hasRequestFailed(graphQLResponse(['data' => []])))->toBeNull()
        ->and($connector->getRequestException(graphQLResponse(['data' => []]), null))->toBeNull()
        ->and($connector->hasRequestFailed(graphQLResponse(['errors' => [['message' => 'x']]])))->toBeTrue();
});

it('lets consumers override the exception', function (): void {
    $connector = new class extends TestConnector
    {
        protected function createGraphQLException(Response $response, ?Throwable $senderException): GraphQLException
        {
            return new CustomGraphQLException($response, previous: $senderException);
        }
    };

    sendThrough($connector, ['errors' => [['message' => 'Boom']]]);
})->throws(CustomGraphQLException::class);

it('works when used on a request', function (): void {
    $connector = new class extends PlainConnector
    {
        use AlwaysThrowOnErrors;
    };
    $request = new class extends TestRequest
    {
        use HandlesGraphQLErrors;
    };

    $connector
        ->withMockClient(new MockClient([MockResponse::make(['errors' => [['message' => 'Boom']]], 200, ['Content-Type' => 'application/json'])]))
        ->send($request);
})->throws(GraphQLException::class);

it('uses the request exception over the connector when both use the trait', function (): void {
    $connector = new class extends TestConnector
    {
        protected function createGraphQLException(Response $response, ?Throwable $senderException): GraphQLException
        {
            return new CustomGraphQLException($response, previous: $senderException);
        }
    };
    $request = new class extends TestRequest
    {
        use HandlesGraphQLErrors;
    };

    try {
        $connector
            ->withMockClient(new MockClient([MockResponse::make(['errors' => [['message' => 'Boom']]], 200, ['Content-Type' => 'application/json'])]))
            ->send($request);
    } catch (GraphQLException $exception) {
        expect($exception::class)->toBe(GraphQLException::class);

        return;
    }

    test()->fail('Expected a GraphQLException.');
});

it('keeps the status-specific exception for an error status with GraphQL errors', function (int $status, string $exception): void {
    expect(fn () => sendThrough(new TestConnector, ['errors' => [['message' => 'Nope']]], $status))
        ->toThrow($exception);
})->with([
    '401' => [401, UnauthorizedException::class],
    '429' => [429, TooManyRequestsException::class],
    '503' => [503, ServiceUnavailableException::class],
]);

it('lets consumers accept partial results', function (): void {
    $connector = new class extends TestConnector
    {
        protected function shouldTreatGraphQLErrorsAsFailure(Response $response): bool
        {
            return ($response->json('data') ?? null) === null;
        }
    };

    expect(sendThrough($connector, ['data' => ['a' => 1], 'errors' => [['message' => 'b failed']]])->status())->toBe(200);
    expect(fn () => sendThrough($connector, ['data' => null, 'errors' => [['message' => 'x']]]))
        ->toThrow(GraphQLException::class);
});
