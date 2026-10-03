<div align="center">
    <h1>Saloon GraphQL</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/benargo/saloon-graphql"><img src="https://img.shields.io/packagist/v/benargo/saloon-graphql.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/benargo/saloon-graphql"><img src="https://img.shields.io/packagist/php-v/benargo/saloon-graphql.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/benargo/saloon-graphql"><img src="https://badge.laravel.cloud/badge/benargo/saloon-graphql?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/benargo/saloon-graphql/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/benargo/saloon-graphql/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/benargo/saloon-graphql"><img src="https://img.shields.io/packagist/dt/benargo/saloon-graphql.svg?style=flat-square" alt="Total Downloads"></a>
</p>

An API-agnostic GraphQL layer for [Saloon](https://docs.saloon.dev) v4. It gives you a base request that sends `{query, variables}` as JSON, and a trait that turns GraphQL `errors` into exceptions, including on HTTP 200 responses.

The package depends only on `saloonphp/saloon`. In a Laravel app, the service provider is auto-discovered, but Laravel is not required.

## Installation

```bash
composer require benargo/saloon-graphql
```

## Usage

### Writing a request

Extend `GraphQLRequest`, then define the endpoint, the GraphQL document and, optionally, the variables:

```php
use Saloon\GraphQL\GraphQLRequest;

class GetCharacter extends GraphQLRequest
{
    public function __construct(private int $id) {}

    public function resolveEndpoint(): string
    {
        return '/graphql';
    }

    protected function graphQLQuery(): string
    {
        return <<<'GRAPHQL'
            query Character($id: Int!) {
                character(id: $id) { id name }
            }
            GRAPHQL;
    }

    protected function variables(): array
    {
        return ['id' => $this->id];
    }
}
```

Requests are sent as `POST` with a JSON body of exactly `{"query": ..., "variables": ...}`. When `variables()` returns an empty array, the request sends `"variables": {}`, because GraphQL requires an object.

The document method is called `graphQLQuery()` because Saloon's `Request::query()` already manages URL query parameters.

### Handling GraphQL errors

GraphQL servers often report errors in a `200 OK` response. Add `HandlesGraphQLErrors` to your connector, or to an individual request, so that those responses count as failures:

```php
use Saloon\GraphQL\Traits\HandlesGraphQLErrors;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class MyApiConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use HandlesGraphQLErrors;

    public function resolveBaseUrl(): string
    {
        return 'https://api.example.com';
    }
}
```

A response counts as a GraphQL failure when it has a JSON content type and a non-empty `errors` list. That includes partial results that carry both `data` and `errors`. All other responses, including malformed JSON and HTML error pages, go through Saloon's normal status-based handling, so a 500 still throws `InternalServerErrorException`. The trait's `hasRequestFailed()` returns `null`, never `false`, for these, so it never hides a failed HTTP status.

### Inspecting the exception

```php
use Saloon\GraphQL\Exceptions\GraphQLException;

try {
    $connector->send(new GetCharacter(42));
} catch (GraphQLException $exception) {
    $exception->getErrors();                    // The raw list of GraphQL errors
    $exception->getFirstError();                // The first error message, or null
    $exception->hasErrorMatching('/not found/i');
    $exception->getResponse();                  // The Saloon response
}
```

`GraphQLException` extends Saloon's `RequestException`. Its default message is `GraphQL request failed: {first error message}`.

### Throwing your own exception

Override `createGraphQLException()` to return a subclass:

```php
use Saloon\GraphQL\Exceptions\GraphQLException;
use Saloon\Http\Response;

class MyApiConnector extends Connector
{
    use HandlesGraphQLErrors;

    protected function createGraphQLException(Response $response, ?Throwable $senderException): GraphQLException
    {
        return new MyApiGraphQLException($response, previous: $senderException);
    }
}
```

### Falling back for non-GraphQL failures

`getRequestException()` returns `null` when a response has no GraphQL errors, so Saloon's default exceptions apply. To use your own exception instead, alias the trait method:

```php
class MyApiConnector extends Connector
{
    use HandlesGraphQLErrors {
        getRequestException as getGraphQLRequestException;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return $this->getGraphQLRequestException($response, $senderException)
            ?? new MyApiException($response, previous: $senderException);
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Saloon GraphQL! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Credits

- [Ben Argo](https://github.com/benargo)
- [All Contributors](../../contributors)

## License

Saloon GraphQL is open-sourced software licensed under the [MIT license](LICENSE.md).
