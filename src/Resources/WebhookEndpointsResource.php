<?php

declare(strict_types=1);

namespace KosovoPay\Resources;

use KosovoPay\Dto\Collection;
use KosovoPay\Dto\DeletedResource;
use KosovoPay\Dto\WebhookEndpoint;
use KosovoPay\Http\Connector;
use KosovoPay\Internal\Cast;
use KosovoPay\Params\CreateWebhookEndpointParams;
use KosovoPay\Requests\Webhooks\CreateEndpoint;
use KosovoPay\Requests\Webhooks\DeleteEndpoint;
use KosovoPay\Requests\Webhooks\ListEndpoints;
use KosovoPay\Requests\Webhooks\RotateSecret;

final readonly class WebhookEndpointsResource
{
    public function __construct(private Connector $connector) {}

    public function create(CreateWebhookEndpointParams $params): WebhookEndpoint
    {
        $request = new CreateEndpoint($params);

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    /** @return Collection<WebhookEndpoint> */
    public function all(): Collection
    {
        $response = $this->connector->send(new ListEndpoints);

        return Collection::fromArray(
            Cast::object($response->json()),
            static fn (array $d): WebhookEndpoint => WebhookEndpoint::fromArray($d),
        );
    }

    public function delete(string $id): DeletedResource
    {
        $request = new DeleteEndpoint($id);

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    public function rotateSecret(string $id): WebhookEndpoint
    {
        $request = new RotateSecret($id);

        return $request->createDtoFromResponse($this->connector->send($request));
    }
}
