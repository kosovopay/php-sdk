<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Banks;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListBanks extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/banks';
    }
}
