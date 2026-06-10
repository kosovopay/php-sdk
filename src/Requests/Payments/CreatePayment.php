<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Payments;

use KosovoPay\Dto\Payment;
use KosovoPay\Http\ApiRequest;
use KosovoPay\Params\CreatePaymentParams;
use KosovoPay\Requests\Concerns\SendsIdempotencyKey;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

final class CreatePayment extends ApiRequest implements HasBody
{
    use HasJsonBody;
    use SendsIdempotencyKey;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreatePaymentParams $params,
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = $idempotencyKey;
    }

    public function resolveEndpoint(): string
    {
        return '/payments';
    }

    /** @return array<string, mixed> */
    protected function defaultBody(): array
    {
        return $this->params->toArray();
    }

    public function createDtoFromResponse(Response $response): Payment
    {
        return Payment::fromArray($this->decoded($response));
    }
}
