<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Webhooks;

use KosovoPay\Dto\WebhookEndpoint;
use KosovoPay\Http\ApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

final class RotateSecret extends ApiRequest
{
    protected Method $method = Method::POST;

    public function __construct(private readonly string $id) {}

    public function resolveEndpoint(): string
    {
        return '/webhook-endpoints/'.rawurlencode($this->id).'/rotate-secret';
    }

    public function createDtoFromResponse(Response $response): WebhookEndpoint
    {
        return WebhookEndpoint::fromArray($this->decoded($response));
    }
}
