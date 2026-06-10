<?php

declare(strict_types=1);

use KosovoPay\Dto\Payment;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function pageRow(string $id): array
{
    return [
        'object' => 'payment', 'id' => $id, 'status' => 'captured', 'mode' => 'test',
        'amount' => 100, 'amount_captured' => 100, 'amount_refunded' => 0, 'currency' => 'EUR',
        'created' => 1, 'refunds' => [],
    ];
}

it('streams across pages until has_more is false, yielding typed Payments', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make(['object' => 'list', 'data' => [pageRow('pi_1'), pageRow('pi_2')], 'has_more' => true, 'url' => '/api/sdk/payments'], 200),
        MockResponse::make(['object' => 'list', 'data' => [pageRow('pi_3')], 'has_more' => false, 'url' => '/api/sdk/payments'], 200),
    ]));

    $ids = [];
    foreach ($kp->payments->all() as $payment) {
        expect($payment)->toBeInstanceOf(Payment::class);
        $ids[] = $payment->id;
    }

    expect($ids)->toBe(['pi_1', 'pi_2', 'pi_3']);
});

it('yields nothing for an empty first page', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make(['object' => 'list', 'data' => [], 'has_more' => false, 'url' => '/api/sdk/payments'], 200),
    ]));

    $count = 0;
    foreach ($kp->payments->all() as $_) {
        $count++;
    }

    expect($count)->toBe(0);
});
