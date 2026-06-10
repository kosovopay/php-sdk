<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Currencies;

use KosovoPay\Dto\Rate;
use KosovoPay\Http\ApiRequest;
use KosovoPay\Params\RetrieveRateParams;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class RetrieveRate extends ApiRequest
{
    protected Method $method = Method::GET;

    public function __construct(private readonly RetrieveRateParams $params) {}

    public function resolveEndpoint(): string
    {
        return '/rates';
    }

    /** @return array<string, string> */
    protected function defaultQuery(): array
    {
        return $this->params->toQuery();
    }

    public function createDtoFromResponse(Response $response): Rate
    {
        return Rate::fromArray($this->decoded($response));
    }
}
