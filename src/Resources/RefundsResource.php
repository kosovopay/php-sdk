<?php

declare(strict_types=1);

namespace KosovoPay\Resources;

use KosovoPay\Dto\Refund;
use KosovoPay\Http\Connector;
use KosovoPay\Http\CursorPaginator;
use KosovoPay\Params\CreateRefundParams;
use KosovoPay\Params\ListRefundsParams;
use KosovoPay\Requests\Refunds\CreateRefund;
use KosovoPay\Requests\Refunds\ListRefunds;
use KosovoPay\Requests\Refunds\RetrieveRefund;

final readonly class RefundsResource
{
    public function __construct(private Connector $connector) {}

    public function create(CreateRefundParams $params, ?string $idempotencyKey = null): Refund
    {
        $request = new CreateRefund($params, $idempotencyKey);

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    public function retrieve(string $id): Refund
    {
        $request = new RetrieveRefund($id);

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    /**
     * Auto-paginating iterator over every matching refund.
     *
     * @return iterable<int, Refund>
     */
    public function all(?ListRefundsParams $params = null): iterable
    {
        $paginator = new CursorPaginator($this->connector, new ListRefunds($params));

        if ($params?->limit !== null) {
            $paginator->setPerPageLimit($params->limit);
        }

        foreach ($paginator->iterateRows() as $row) {
            yield Refund::fromArray($row);
        }
    }
}
