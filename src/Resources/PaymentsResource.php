<?php

declare(strict_types=1);

namespace KosovoPay\Resources;

use KosovoPay\Dto\Collection;
use KosovoPay\Dto\Payment;
use KosovoPay\Dto\TimelineEvent;
use KosovoPay\Http\Connector;
use KosovoPay\Http\CursorPaginator;
use KosovoPay\Internal\Cast;
use KosovoPay\Params\CreatePaymentParams;
use KosovoPay\Params\ListPaymentsParams;
use KosovoPay\Requests\Payments\CancelPayment;
use KosovoPay\Requests\Payments\CreatePayment;
use KosovoPay\Requests\Payments\ListPayments;
use KosovoPay\Requests\Payments\PaymentTimeline;
use KosovoPay\Requests\Payments\RetrievePayment;

final readonly class PaymentsResource
{
    public function __construct(private Connector $connector) {}

    public function create(CreatePaymentParams $params, ?string $idempotencyKey = null): Payment
    {
        $request = new CreatePayment($params, $idempotencyKey);

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    public function retrieve(string $id): Payment
    {
        $request = new RetrievePayment($id);

        return $request->createDtoFromResponse($this->connector->send($request));
    }

    /**
     * Auto-paginating iterator over every matching payment, newest first.
     *
     * @return iterable<int, Payment>
     */
    public function all(?ListPaymentsParams $params = null): iterable
    {
        $paginator = new CursorPaginator($this->connector, new ListPayments($params));

        if ($params?->limit !== null) {
            $paginator->setPerPageLimit($params->limit);
        }

        foreach ($paginator->iterateRows() as $row) {
            yield Payment::fromArray($row);
        }
    }

    /** @return Collection<TimelineEvent> */
    public function timeline(string $id): Collection
    {
        $response = $this->connector->send(new PaymentTimeline($id));

        return Collection::fromArray(
            Cast::object($response->json()),
            static fn (array $d): TimelineEvent => TimelineEvent::fromArray($d),
        );
    }

    public function cancel(string $id, ?string $reason = null): Payment
    {
        $request = new CancelPayment($id, $reason);

        return $request->createDtoFromResponse($this->connector->send($request));
    }
}
