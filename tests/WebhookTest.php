<?php

declare(strict_types=1);

use KosovoPay\Enums\WebhookEventType;
use KosovoPay\Exceptions\WebhookSignatureException;
use KosovoPay\Webhook;

function signed(string $body, string $secret, int $t): string
{
    $v1 = hash_hmac('sha256', $t.'.'.$body, $secret);

    return "t={$t},v1={$v1}";
}

it('constructs a typed Event from a valid signature', function () {
    $secret = 'whsec_test';
    $now = time();
    $body = json_encode([
        'id' => 'evt_1', 'object' => 'event', 'type' => 'payment.captured', 'created' => $now,
        'livemode' => false, 'api_version' => '2026-06-01',
        'data' => ['object' => ['object' => 'payment', 'id' => 'pi_1', 'status' => 'captured', 'mode' => 'test', 'amount' => 100, 'amount_captured' => 100, 'amount_refunded' => 0, 'currency' => 'EUR', 'created' => 1, 'refunds' => []]],
    ], JSON_THROW_ON_ERROR);

    $event = Webhook::constructEvent($body, signed($body, $secret, $now), $secret, tolerance: 300);

    expect($event->type)->toBe(WebhookEventType::PaymentCaptured)
        ->and($event->asPayment()->id)->toBe('pi_1');
});

it('verifies a good signature and rejects tamper / wrong secret / stale / malformed', function () {
    $secret = 'whsec_test';
    $now = 1_749_600_000;
    $body = '{"hello":"world"}';
    $header = signed($body, $secret, $now);

    expect(Webhook::verify($body, $header, $secret, $now))->toBeTrue();
    expect(Webhook::verify($body.'x', $header, $secret, $now))->toBeFalse();       // tampered body
    expect(Webhook::verify($body, $header, 'wrong', $now))->toBeFalse();           // wrong secret
    expect(Webhook::verify($body, $header, $secret, $now + 9999))->toBeFalse();    // stale (replay)
    expect(Webhook::verify($body, 'garbage', $secret, $now))->toBeFalse();         // malformed header
});

it('throws WebhookSignatureException on constructEvent with a bad signature', function () {
    expect(fn () => Webhook::constructEvent('{"a":1}', 't=1,v1=bad', 'secret'))
        ->toThrow(WebhookSignatureException::class);
});
