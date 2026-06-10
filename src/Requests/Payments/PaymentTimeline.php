<?php

declare(strict_types=1);

namespace KosovoPay\Requests\Payments;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class PaymentTimeline extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $id) {}

    public function resolveEndpoint(): string
    {
        return '/payments/'.rawurlencode($this->id).'/timeline';
    }
}
