<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use DateTimeImmutable;
use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\BankMode;
use KosovoPay\Enums\CheckoutMode;
use KosovoPay\Enums\CurrencyCode;
use KosovoPay\Enums\PaymentStatus;
use KosovoPay\Internal\Cast;

final readonly class Payment
{
    /**
     * @param  list<array<array-key, mixed>>|null  $lineItems
     * @param  array<string, mixed>  $metadata
     * @param  list<Refund>  $refunds
     */
    public function __construct(
        public string $id,
        public PaymentStatus $status,
        public BankMode $mode,
        public int $amount,
        public int $amountCaptured,
        public int $amountRefunded,
        public CurrencyCode $currency,
        public ?BankCode $bankCode,
        public ?string $merchantReference,
        public ?string $description,
        public ?Payer $payer,
        public ?array $lineItems,
        public array $metadata,
        public ?Fx $fx,
        public ?string $lastError,
        public ?int $expires,
        public ?int $captured,
        public int $created,
        public array $refunds,
        public ?CheckoutMode $checkoutMode = null,
        public ?string $hostedUrl = null,
        public ?string $redirectUrl = null,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        $bankCode = Cast::nullableString($d['bank_code'] ?? null);
        $checkoutMode = Cast::nullableString($d['checkout_mode'] ?? null);
        $lineItems = isset($d['line_items']) && is_array($d['line_items']) ? Cast::rows($d, 'line_items') : null;

        return new self(
            id: Cast::string($d['id'] ?? null),
            status: PaymentStatus::tryFromWire(Cast::nullableString($d['status'] ?? null)),
            mode: BankMode::tryFromWire(Cast::nullableString($d['mode'] ?? null)),
            amount: Cast::int($d['amount'] ?? null),
            amountCaptured: Cast::int($d['amount_captured'] ?? null),
            amountRefunded: Cast::int($d['amount_refunded'] ?? null),
            currency: CurrencyCode::tryFromWire(Cast::nullableString($d['currency'] ?? null)),
            bankCode: $bankCode === null ? null : BankCode::tryFromWire($bankCode),
            merchantReference: Cast::nullableString($d['merchant_reference'] ?? null),
            description: Cast::nullableString($d['description'] ?? null),
            payer: is_array($d['payer'] ?? null) ? Payer::fromArray($d['payer']) : null,
            lineItems: $lineItems,
            metadata: Cast::map($d, 'metadata'),
            fx: is_array($d['fx'] ?? null) ? Fx::fromArray($d['fx']) : null,
            lastError: Cast::nullableString($d['last_error'] ?? null),
            expires: Cast::nullableInt($d['expires_at'] ?? null),
            captured: Cast::nullableInt($d['captured_at'] ?? null),
            created: Cast::int($d['created'] ?? null),
            refunds: array_map(static fn (array $r): Refund => Refund::fromArray($r), Cast::rows($d, 'refunds')),
            checkoutMode: $checkoutMode === null ? null : CheckoutMode::tryFrom($checkoutMode),
            hostedUrl: Cast::nullableString($d['hosted_url'] ?? null),
            redirectUrl: Cast::nullableString($d['redirect_url'] ?? null),
        );
    }

    public function createdAt(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->created);
    }
}
