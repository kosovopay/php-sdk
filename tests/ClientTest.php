<?php

declare(strict_types=1);

use KosovoPay\Dto\Bank;
use KosovoPay\Dto\Me;
use KosovoPay\Dto\Payment;
use KosovoPay\Dto\Refund;
use KosovoPay\Dto\WebhookEndpoint;
use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\CheckoutMode;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Enums\PaymentStatus;
use KosovoPay\Enums\RefundReason;
use KosovoPay\Enums\WebhookEventType;
use KosovoPay\Params\CreatePaymentParams;
use KosovoPay\Params\CreateRefundParams;
use KosovoPay\Params\CreateWebhookEndpointParams;
use KosovoPay\Requests\Payments\CreatePayment;
use KosovoPay\Requests\Refunds\CreateRefund;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('retrieves me() as a typed Me dto', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'me',
            'team' => ['id' => 'team_1', 'name' => 'Acme', 'logo_url' => null],
            'mode' => 'test',
            'key_prefix' => 'sk_test_ab',
            'enabled_banks' => ['onefor'],
            'default_currency' => 'EUR',
        ], 200),
    ]));

    $me = $kp->me();

    expect($me)->toBeInstanceOf(Me::class)
        ->and($me->team->name)->toBe('Acme')
        ->and($me->mode->value)->toBe('test')
        ->and($me->enabledBanks[0])->toBe(BankCode::Onefor)
        ->and($me->defaultCurrency)->toBe(CurrencyCode::EUR);
});

it('lists banks as a typed Collection<Bank> with capabilities', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'list',
            'data' => [[
                'object' => 'bank', 'code' => 'onefor', 'display_name' => 'Onefor', 'logo_url' => null,
                'enabled' => true, 'modes' => ['test'],
                'capabilities' => ['currencies' => ['EUR'], 'min_amount' => 150, 'amount_step' => 1, 'refunds' => ['supported' => true, 'partial' => false]],
            ]],
            'has_more' => false, 'url' => '/api/sdk/banks',
        ], 200),
    ]));

    $banks = $kp->banks->all();

    expect($banks)->toHaveCount(1)
        ->and($banks->data[0])->toBeInstanceOf(Bank::class)
        ->and($banks->data[0]->code)->toBe(BankCode::Onefor)
        ->and($banks->data[0]->capabilities->minAmount)->toBe(150)
        ->and($banks->data[0]->capabilities->currencies[0])->toBe(CurrencyCode::EUR);
});

it('creates a payment and returns a typed Payment', function () {
    $kp = fakeClient(new MockClient([
        CreatePayment::class => MockResponse::make([
            'object' => 'payment', 'id' => 'pi_1', 'status' => 'pending', 'mode' => 'test',
            'amount' => 4990, 'amount_captured' => 0, 'amount_refunded' => 0, 'currency' => 'EUR',
            'bank_code' => 'onefor', 'merchant_reference' => 'O-1', 'description' => null,
            'payer' => null, 'line_items' => null, 'metadata' => ['order_id' => 'O-1'],
            'fx' => null, 'last_error' => null, 'expires_at' => null, 'captured_at' => null,
            'created' => 1749600000, 'refunds' => [],
            'checkout_mode' => 'direct', 'hosted_url' => null, 'redirect_url' => 'https://bank/redirect',
        ], 201),
    ]));

    $payment = $kp->payments->create(new CreatePaymentParams(
        amount: 4990,
        currency: CurrencyCode::EUR,
        successUrl: 'https://shop.test/ok',
        mode: CheckoutMode::Direct,
        bankCode: BankCode::Onefor,
        metadata: ['order_id' => 'O-1'],
    ), idempotencyKey: 'order-1');

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toBe(4990)
        ->and($payment->checkoutMode)->toBe(CheckoutMode::Direct)
        ->and($payment->redirectUrl)->toBe('https://bank/redirect');
});

it('sends an Idempotency-Key on create', function () {
    $mock = new MockClient([CreatePayment::class => MockResponse::make(['object' => 'payment', 'id' => 'pi_1', 'status' => 'pending', 'mode' => 'test', 'amount' => 200, 'amount_captured' => 0, 'amount_refunded' => 0, 'currency' => 'EUR', 'created' => 1, 'refunds' => []], 201)]);
    $kp = fakeClient($mock);

    $kp->payments->create(new CreatePaymentParams(amount: 200, currency: CurrencyCode::EUR, successUrl: 'https://x.test/ok'));

    $mock->assertSent(fn (Request $r) => $r->headers()->get('Idempotency-Key') !== null);
});

it('creates a refund', function () {
    $kp = fakeClient(new MockClient([
        CreateRefund::class => MockResponse::make([
            'object' => 'refund', 'id' => 're_1', 'payment' => 'pi_1', 'amount' => 1000,
            'status' => 'succeeded', 'reason' => 'requested_by_customer', 'failure_reason' => null,
            'created' => 1749601000, 'succeeded_at' => 1749601002,
        ], 201),
    ]));

    $refund = $kp->refunds->create(new CreateRefundParams(payment: 'pi_1', amount: 1000, reason: RefundReason::RequestedByCustomer));

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->amount)->toBe(1000)
        ->and($refund->reason)->toBe(RefundReason::RequestedByCustomer);
});

it('creates a webhook endpoint and exposes the secret once', function () {
    $kp = fakeClient(new MockClient([
        MockResponse::make([
            'object' => 'webhook_endpoint', 'id' => 'we_1', 'url' => 'https://shop.test/hooks',
            'description' => null, 'enabled_events' => ['payment.captured'], 'status' => 'enabled',
            'mode' => 'test', 'created' => 1749600000, 'secret' => 'whsec_abc',
        ], 201),
    ]));

    $endpoint = $kp->webhookEndpoints->create(new CreateWebhookEndpointParams(
        url: 'https://shop.test/hooks',
        enabledEvents: [WebhookEventType::PaymentCaptured],
    ));

    expect($endpoint)->toBeInstanceOf(WebhookEndpoint::class)
        ->and($endpoint->secret)->toBe('whsec_abc')
        ->and($endpoint->enabledEvents[0])->toBe(WebhookEventType::PaymentCaptured);
});

it('rejects an invalid params object before any HTTP call', function () {
    expect(fn () => new CreatePaymentParams(amount: 0, currency: CurrencyCode::EUR, successUrl: 'https://x/ok'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new CreatePaymentParams(amount: 100, currency: CurrencyCode::EUR, successUrl: 'https://x/ok', mode: CheckoutMode::Direct))
        ->toThrow(InvalidArgumentException::class); // direct needs bankCode
});
