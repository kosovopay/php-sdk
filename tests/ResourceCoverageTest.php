<?php

declare(strict_types=1);

use KosovoPay\Dto\Bank;
use KosovoPay\Dto\Collection;
use KosovoPay\Dto\Currency;
use KosovoPay\Dto\DeletedResource;
use KosovoPay\Dto\Payment;
use KosovoPay\Dto\Rate;
use KosovoPay\Dto\Refund;
use KosovoPay\Dto\TimelineEvent;
use KosovoPay\Dto\WebhookEndpoint;
use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Enums\PaymentStatus;
use KosovoPay\Enums\RefundStatus;
use KosovoPay\Params\CreateRefundParams;
use KosovoPay\Requests\Refunds\CreateRefund;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('retrieves a payment by id and hits the right endpoint', function () {
    $mock = new MockClient([
        MockResponse::make([
            'object' => 'payment', 'id' => 'pi_42', 'status' => 'captured', 'mode' => 'test',
            'amount' => 1500, 'amount_captured' => 1500, 'amount_refunded' => 0, 'currency' => 'EUR',
            'created' => 1749600000, 'refunds' => [],
        ], 200),
    ]);
    $kp = fakeClient($mock);

    $payment = $kp->payments->retrieve('pi_42');

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->id)->toBe('pi_42')
        ->and($payment->status)->toBe(PaymentStatus::Captured);

    $mock->assertSent(fn (Request $r) => $r->resolveEndpoint() === '/payments/pi_42');
});

it('reads a payment timeline as a typed Collection<TimelineEvent>', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'list',
            'data' => [
                ['type' => 'payment.created', 'at' => 1749600000],
                ['type' => 'payment.captured', 'at' => 1749600100],
            ],
            'has_more' => false, 'url' => '/api/sdk/payments/pi_42/timeline',
        ], 200),
    ]));

    $timeline = $kp->payments->timeline('pi_42');

    expect($timeline)->toBeInstanceOf(Collection::class)
        ->and($timeline)->toHaveCount(2)
        ->and($timeline->data[0])->toBeInstanceOf(TimelineEvent::class)
        ->and($timeline->data[1]->type)->toBe('payment.captured');
});

it('cancels a payment', function () {
    $mock = new MockClient([
        MockResponse::make([
            'object' => 'payment', 'id' => 'pi_42', 'status' => 'canceled', 'mode' => 'test',
            'amount' => 1500, 'amount_captured' => 0, 'amount_refunded' => 0, 'currency' => 'EUR',
            'created' => 1749600000, 'refunds' => [],
        ], 200),
    ]);
    $kp = fakeClient($mock);

    $payment = $kp->payments->cancel('pi_42', reason: 'duplicate');

    expect($payment->status)->toBe(PaymentStatus::Canceled);
    $mock->assertSent(fn (Request $r) => $r->resolveEndpoint() === '/payments/pi_42/cancel');
});

it('retrieves a refund by id', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'refund', 'id' => 're_9', 'payment' => 'pi_1', 'amount' => 500,
            'status' => 'pending', 'reason' => null, 'failure_reason' => null,
            'created' => 1749601000, 'succeeded_at' => null,
        ], 200),
    ]));

    $refund = $kp->refunds->retrieve('re_9');

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->status)->toBe(RefundStatus::Pending)
        ->and($refund->reason)->toBeNull();
});

it('sends an Idempotency-Key on refund create', function () {
    $mock = new MockClient([
        CreateRefund::class => MockResponse::make([
            'object' => 'refund', 'id' => 're_1', 'payment' => 'pi_1', 'amount' => 100,
            'status' => 'succeeded', 'reason' => null, 'failure_reason' => null,
            'created' => 1, 'succeeded_at' => 2,
        ], 201),
    ]);
    $kp = fakeClient($mock);

    $kp->refunds->create(new CreateRefundParams(payment: 'pi_1', amount: 100));

    $mock->assertSent(fn (Request $r) => $r->headers()->get('Idempotency-Key') !== null);
});

it('lists currencies as a typed Collection<Currency>', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'list',
            'data' => [['object' => 'currency', 'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'is_default' => true]],
            'has_more' => false, 'url' => '/api/sdk/currencies',
        ], 200),
    ]));

    $currencies = $kp->currencies->all();

    expect($currencies->data[0])->toBeInstanceOf(Currency::class)
        ->and($currencies->data[0]->code)->toBe(CurrencyCode::EUR)
        ->and($currencies->data[0]->isDefault)->toBeTrue();
});

it('retrieves an FX rate', function () {
    $mock = new MockClient([
        MockResponse::make([
            'object' => 'rate', 'from' => 'EUR', 'to' => 'USD', 'rate' => '1.0850',
            'synced_at' => '2026-06-10T00:00:00Z', 'stale' => false,
        ], 200),
    ]);
    $kp = fakeClient($mock);

    $rate = $kp->rates->retrieve(CurrencyCode::EUR, CurrencyCode::USD);

    expect($rate)->toBeInstanceOf(Rate::class)
        ->and($rate->rate)->toBe('1.0850')
        ->and($rate->stale)->toBeFalse();
    $mock->assertSent(fn (Request $r) => str_contains($r->resolveEndpoint(), '/rates'));
});

it('retrieves a single bank', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'bank', 'code' => 'procredit', 'display_name' => 'ProCredit', 'logo_url' => null,
            'enabled' => true, 'modes' => ['test', 'live'],
            'capabilities' => ['currencies' => ['EUR'], 'min_amount' => 100, 'amount_step' => 1, 'refunds' => ['supported' => true, 'partial' => true]],
        ], 200),
    ]));

    $bank = $kp->banks->retrieve(BankCode::Procredit);

    expect($bank)->toBeInstanceOf(Bank::class)
        ->and($bank->code)->toBe(BankCode::Procredit)
        ->and($bank->capabilities->refunds->partial)->toBeTrue();
});

it('lists, rotates, and deletes webhook endpoints', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'list',
            'data' => [['object' => 'webhook_endpoint', 'id' => 'we_1', 'url' => 'https://x/h', 'description' => null, 'enabled_events' => ['payment.captured'], 'status' => 'enabled', 'mode' => 'test', 'created' => 1, 'secret' => null]],
            'has_more' => false, 'url' => '/api/sdk/webhook-endpoints',
        ], 200),
        MockResponse::make(['object' => 'webhook_endpoint', 'id' => 'we_1', 'url' => 'https://x/h', 'description' => null, 'enabled_events' => ['payment.captured'], 'status' => 'enabled', 'mode' => 'test', 'created' => 1, 'secret' => 'whsec_new'], 200),
        MockResponse::make(['object' => 'webhook_endpoint', 'id' => 'we_1', 'deleted' => true], 200),
    ]));

    $list = $kp->webhookEndpoints->all();
    expect($list->data[0])->toBeInstanceOf(WebhookEndpoint::class);

    $rotated = $kp->webhookEndpoints->rotateSecret('we_1');
    expect($rotated->secret)->toBe('whsec_new');

    $deleted = $kp->webhookEndpoints->delete('we_1');
    expect($deleted)->toBeInstanceOf(DeletedResource::class)
        ->and($deleted->deleted)->toBeTrue();
});
