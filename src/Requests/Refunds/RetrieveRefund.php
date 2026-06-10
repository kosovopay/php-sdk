<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Refunds;

use KosovoPay\Dto\Refund;
use KosovoPay\Http\ApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class RetrieveRefund extends ApiRequest
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $id) {}

    public function resolveEndpoint(): string
    {
        return '/refunds/'.rawurlencode($this->id);
    }

    public function createDtoFromResponse(Response $response): Refund
    {
        return Refund::fromArray($this->decoded($response));
    }
}
