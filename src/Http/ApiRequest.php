<?php

declare(strict_types=1);

namespace KosovoPay\Http;

use KosovoPay\Internal\Cast;
use Saloon\Http\Request as SaloonRequest;
use Saloon\Http\Response;

/**
 * Base for every KosovoPay request. Exposes the decoded body as a definite
 * array type so DTO factories receive a typed value, not `mixed`.
 */
abstract class ApiRequest extends SaloonRequest
{
    /** @return array<array-key, mixed> */
    protected function decoded(Response $response): array
    {
        return Cast::object($response->json());
    }
}
