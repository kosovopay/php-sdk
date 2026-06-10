<?php

declare(strict_types=1);

namespace KosovoPay\Params;

final readonly class LineItem
{
    public function __construct(
        public string $name,
        public int $quantity,
        public int $unitAmountCents,
        public ?string $sku = null,
        public ?string $imageUrl = null,
        public ?string $variant = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_amount_cents' => $this->unitAmountCents,
            'sku' => $this->sku,
            'image_url' => $this->imageUrl,
            'variant' => $this->variant,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
