<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Payments;

use KosovoPay\Dto\Payment;
use KosovoPay\Http\ApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class RetrievePayment extends ApiRequest
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $id) {}

    public function resolveEndpoint(): string
    {
        return '/payments/'.rawurlencode($this->id);
    }

    public function createDtoFromResponse(Response $response): Payment
    {
        return Payment::fromArray($this->decoded($response));
    }
}
