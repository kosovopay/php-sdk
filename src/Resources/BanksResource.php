<?php

declare(strict_types=1);

namespace KosovoPay\Resources;

use KosovoPay\Dto\Bank;
use KosovoPay\Dto\Collection;
use KosovoPay\Enums\BankCode;
use KosovoPay\Http\Connector;
use KosovoPay\Internal\Cast;
use KosovoPay\Requests\Banks\ListBanks;
use KosovoPay\Requests\Banks\RetrieveBank;

final readonly class BanksResource
{
    public function __construct(private Connector $connector) {}

    /** @return Collection<Bank> */
    public function all(): Collection
    {
        $response = $this->connector->send(new ListBanks);

        return Collection::fromArray(
            Cast::object($response->json()),
            static fn (array $d): Bank => Bank::fromArray($d),
        );
    }

    public function retrieve(BankCode $code): Bank
    {
        $request = new RetrieveBank($code);

        return $request->createDtoFromResponse($this->connector->send($request));
    }
}
