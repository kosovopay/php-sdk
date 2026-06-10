<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Payments;

use KosovoPay\Dto\Payment;
use KosovoPay\Http\ApiRequest;
use KosovoPay\Requests\Concerns\SendsIdempotencyKey;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

final class CancelPayment extends ApiRequest implements HasBody
{
    use HasJsonBody;
    use SendsIdempotencyKey;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $id,
        private readonly ?string $reason = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/payments/'.rawurlencode($this->id).'/cancel';
    }

    /** @return array<string, mixed> */
    protected function defaultBody(): array
    {
        return $this->reason === null ? [] : ['reason' => $this->reason];
    }

    public function createDtoFromResponse(Response $response): Payment
    {
        return Payment::fromArray($this->decoded($response));
    }
}
