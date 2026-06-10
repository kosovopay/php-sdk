<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Me;

use KosovoPay\Dto\Me;
use KosovoPay\Http\ApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class RetrieveMe extends ApiRequest
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/me';
    }

    public function createDtoFromResponse(Response $response): Me
    {
        return Me::fromArray($this->decoded($response));
    }
}
