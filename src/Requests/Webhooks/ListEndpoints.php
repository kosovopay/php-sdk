<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Webhooks;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListEndpoints extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/webhook-endpoints';
    }
}
