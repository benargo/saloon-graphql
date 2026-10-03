<?php

declare(strict_types=1);

use Saloon\Enums\Method;
use Saloon\GraphQL\GraphQLRequest;
use Saloon\GraphQL\Tests\Fixtures\PlainConnector;
use Saloon\GraphQL\Tests\Fixtures\TestRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function requestWithoutVariables(): GraphQLRequest
{
    return new class extends GraphQLRequest
    {
        public function resolveEndpoint(): string
        {
            return '/graphql';
        }

        protected function graphQLQuery(): string
        {
            return '{ ping }';
        }
    };
}

it('uses the POST method', function (): void {
    expect((new TestRequest)->getMethod())->toBe(Method::POST);
});

it('builds a body of exactly query and variables', function (): void {
    expect((new TestRequest)->body()->all())->toBe([
        'query' => TestRequest::QUERY,
        'variables' => ['id' => 1],
    ]);
});

it('defaults variables to an empty object', function (): void {
    expect(requestWithoutVariables()->body()->all())->toEqual(['query' => '{ ping }', 'variables' => new stdClass]);
});

it('sends a JSON body with a JSON content type', function (): void {
    $connector = (new PlainConnector)->withMockClient(new MockClient([MockResponse::make(['data' => []])]));

    $psrRequest = $connector->send(new TestRequest)->getPsrRequest();

    expect($psrRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string) $psrRequest->getBody(), true, flags: JSON_THROW_ON_ERROR))
        ->toBe(['query' => TestRequest::QUERY, 'variables' => ['id' => 1]]);
});

it('encodes default variables as an empty JSON object on the wire', function (): void {
    // GraphQL requires variables to be a map: {} rather than [].
    $connector = (new PlainConnector)->withMockClient(new MockClient([MockResponse::make(['data' => []])]));

    expect((string) $connector->send(requestWithoutVariables())->getPsrRequest()->getBody())
        ->toBe('{"query":"{ ping }","variables":{}}');
});

it('rejects variables that are a list', function (): void {
    $request = new class extends TestRequest
    {
        protected function variables(): array
        {
            return ['a', 'b'];
        }
    };

    $request->body()->all();
})->throws(InvalidArgumentException::class, 'must be a map');
