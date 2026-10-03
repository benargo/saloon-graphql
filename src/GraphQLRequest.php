<?php

declare(strict_types=1);

namespace Saloon\GraphQL;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use stdClass;

abstract class GraphQLRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * The GraphQL document to send.
     */
    abstract protected function graphQLQuery(): string;

    /**
     * The variables to send with the query.
     *
     * @return array<string, mixed>
     */
    protected function variables(): array
    {
        return [];
    }

    /**
     * Empty variables are sent as an object ({}), never a JSON array ([]),
     * because GraphQL requires variables to be a map.
     *
     * @return array{query: string, variables: array<string, mixed>|stdClass}
     */
    protected function defaultBody(): array
    {
        return [
            'query' => $this->graphQLQuery(),
            'variables' => $this->variables() ?: new stdClass,
        ];
    }
}
