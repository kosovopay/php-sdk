<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Webhooks;

use KosovoPay\Dto\WebhookEndpoint;
use KosovoPay\Http\ApiRequest;
use KosovoPay\Params\CreateWebhookEndpointParams;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

final class CreateEndpoint extends ApiRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(private readonly CreateWebhookEndpointParams $params) {}

    public function resolveEndpoint(): string
    {
        return '/webhook-endpoints';
    }

    /** @return array<string, mixed> */
    protected function defaultBody(): array
    {
        return $this->params->toArray();
    }

    public function createDtoFromResponse(Response $response): WebhookEndpoint
    {
        return WebhookEndpoint::fromArray($this->decoded($response));
    }
}
