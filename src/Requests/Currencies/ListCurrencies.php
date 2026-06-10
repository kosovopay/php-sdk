<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Currencies;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListCurrencies extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/currencies';
    }
}
