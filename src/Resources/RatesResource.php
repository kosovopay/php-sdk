<?php

declare(strict_types=1);

namespace KosovoPay\Resources;

use KosovoPay\Dto\Rate;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Http\Connector;
use KosovoPay\Params\RetrieveRateParams;
use KosovoPay\Requests\Currencies\RetrieveRate;

final readonly class RatesResource
{
    public function __construct(private Connector $connector) {}

    public function retrieve(CurrencyCode $from, CurrencyCode $to): Rate
    {
        $request = new RetrieveRate(new RetrieveRateParams($from, $to));

        return $request->createDtoFromResponse($this->connector->send($request));
    }
}
