<?php

declare(strict_types=1);

namespace KosovoPay\Params;

use InvalidArgumentException;
use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\CheckoutMode;
use KosovoPay\Enums\CurrencyCode;

final readonly class CreatePaymentParams
{
    /**
     * @param  int  $amount  minor units, must be > 0
     * @param  list<LineItem>  $lineItems
     * @param  array<string, scalar>  $metadata
     */
    public function __construct(
        public int $amount,
        public CurrencyCode $currency,
        public string $successUrl,
        public CheckoutMode $mode = CheckoutMode::Hosted,
        public ?BankCode $bankCode = null,
        public ?string $cancelUrl = null,
        public ?string $failUrl = null,
        public ?string $description = null,
        public array $lineItems = [],
        public array $metadata = [],
        public ?int $expiresAt = null,
        public ?string $merchantReference = null,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('amount must be a positive integer in minor units.');
        }
        if ($mode === CheckoutMode::Direct && $bankCode === null) {
            throw new InvalidArgumentException('bankCode is required for direct checkout mode.');
        }
        if ($mode === CheckoutMode::Hosted && $bankCode !== null) {
            throw new InvalidArgumentException('bankCode must be omitted for hosted checkout mode.');
        }
        self::assertUrl($successUrl, 'successUrl');
        if ($cancelUrl !== null) {
            self::assertUrl($cancelUrl, 'cancelUrl');
        }
        if ($failUrl !== null) {
            self::assertUrl($failUrl, 'failUrl');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'currency' => $this->currency->value,
            'mode' => $this->mode->value,
            'bank_code' => $this->bankCode?->value,
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
            'fail_url' => $this->failUrl,
            'description' => $this->description,
            'line_items' => $this->lineItems === []
                ? null
                : array_map(static fn (LineItem $i): array => $i->toArray(), $this->lineItems),
            'metadata' => $this->metadata === [] ? null : $this->metadata,
            'expires_at' => $this->expiresAt,
            'merchant_reference' => $this->merchantReference,
        ], static fn (mixed $v): bool => $v !== null);
    }

    private static function assertUrl(string $url, string $field): void
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("{$field} must be an http or https URL.");
        }
    }
}
