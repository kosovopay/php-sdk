<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Payments;

use KosovoPay\Params\ListPaymentsParams;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\Paginatable;

final class ListPayments extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(private readonly ?ListPaymentsParams $params = null) {}

    public function resolveEndpoint(): string
    {
        return '/payments';
    }

    /** @return array<string, mixed> */
    protected function defaultQuery(): array
    {
        return $this->params?->toQuery() ?? [];
    }
}
