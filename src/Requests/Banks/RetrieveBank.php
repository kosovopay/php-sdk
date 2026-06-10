<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Banks;

use KosovoPay\Dto\Bank;
use KosovoPay\Enums\BankCode;
use KosovoPay\Http\ApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class RetrieveBank extends ApiRequest
{
    protected Method $method = Method::GET;

    public function __construct(private readonly BankCode $code) {}

    public function resolveEndpoint(): string
    {
        return '/banks/'.$this->code->value;
    }

    public function createDtoFromResponse(Response $response): Bank
    {
        return Bank::fromArray($this->decoded($response));
    }
}
