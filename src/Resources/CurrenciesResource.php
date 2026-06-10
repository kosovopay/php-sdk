<?php

declare(strict_types=1);

namespace KosovoPay\Resources;

use KosovoPay\Dto\Collection;
use KosovoPay\Dto\Currency;
use KosovoPay\Http\Connector;
use KosovoPay\Internal\Cast;
use KosovoPay\Requests\Currencies\ListCurrencies;

final readonly class CurrenciesResource
{
    public function __construct(private Connector $connector) {}

    /** @return Collection<Currency> */
    public function all(): Collection
    {
        $response = $this->connector->send(new ListCurrencies);

        return Collection::fromArray(
            Cast::object($response->json()),
            static fn (array $d): Currency => Currency::fromArray($d),
        );
    }
}
