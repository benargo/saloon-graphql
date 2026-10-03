<?php

declare(strict_types=1);

namespace Saloon\GraphQL;

use InvalidArgumentException;
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
     * The variables to send with the query, keyed by variable name.
     *
     * @return array<int|string, mixed>
     */
    protected function variables(): array
    {
        return [];
    }

    /**
     * Empty variables are sent as an object ({}), never a JSON array ([]),
     * because GraphQL requires variables to be a map. A non-empty list is
     * rejected. Return a stdClass for an empty nested input object, since an
     * empty array is encoded as [].
     *
     * @return array{query: string, variables: array<int|string, mixed>|stdClass}
     *
     * @throws InvalidArgumentException If variables() returns a list rather than a map.
     */
    protected function defaultBody(): array
    {
        $variables = $this->variables();

        if ($variables !== [] && array_is_list($variables)) {
            throw new InvalidArgumentException('GraphQL variables must be a map of names to values, not a list.');
        }

        return [
            'query' => $this->graphQLQuery(),
            'variables' => $variables ?: new stdClass,
        ];
    }
}
