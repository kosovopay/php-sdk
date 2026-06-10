<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Refunds;

use KosovoPay\Params\ListRefundsParams;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\Paginatable;

final class ListRefunds extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(private readonly ?ListRefundsParams $params = null) {}

    public function resolveEndpoint(): string
    {
        return '/refunds';
    }

    /** @return array<string, mixed> */
    protected function defaultQuery(): array
    {
        return $this->params?->toQuery() ?? [];
    }
}
