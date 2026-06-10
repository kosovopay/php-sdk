<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Refunds;

use KosovoPay\Dto\Refund;
use KosovoPay\Http\ApiRequest;
use KosovoPay\Params\CreateRefundParams;
use KosovoPay\Requests\Concerns\SendsIdempotencyKey;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

final class CreateRefund extends ApiRequest implements HasBody
{
    use HasJsonBody;
    use SendsIdempotencyKey;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateRefundParams $params,
        ?string $idempotencyKey = null,
    ) {
        $this->idempotencyKey = $idempotencyKey;
    }

    public function resolveEndpoint(): string
    {
        return '/refunds';
    }

    /** @return array<string, mixed> */
    protected function defaultBody(): array
    {
        return $this->params->toArray();
    }

    public function createDtoFromResponse(Response $response): Refund
    {
        return Refund::fromArray($this->decoded($response));
    }
}
