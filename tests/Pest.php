<?php

declare(strict_types=1);

use Saloon\GraphQL\Tests\Fixtures\PlainConnector;
use Saloon\GraphQL\Tests\Fixtures\TestRequest;
use Saloon\GraphQL\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;

pest()->extend(TestCase::class)->in('Feature');

/**
 * Send a TestRequest through a connector without error handling, so the
 * mocked response comes back without being thrown.
 *
 * @param  array<string, mixed>|string  $body
 * @param  array<string, string>  $headers
 */
function graphQLResponse(array|string $body, int $status = 200, array $headers = ['Content-Type' => 'application/json']): Response
{
    return (new PlainConnector)
        ->withMockClient(new MockClient([MockResponse::make($body, $status, $headers)]))
        ->send(new TestRequest);
}
