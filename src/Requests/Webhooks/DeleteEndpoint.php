<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Webhooks;

use KosovoPay\Dto\DeletedResource;
use KosovoPay\Http\ApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class DeleteEndpoint extends ApiRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(private readonly string $id) {}

    public function resolveEndpoint(): string
    {
        return '/webhook-endpoints/'.rawurlencode($this->id);
    }

    public function createDtoFromResponse(Response $response): DeletedResource
    {
        return DeletedResource::fromArray($this->decoded($response));
    }
}
