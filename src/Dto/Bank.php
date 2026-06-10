<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Enums\BankCode;
use KosovoPay\Enums\BankMode;
use KosovoPay\Internal\Cast;

final readonly class Bank
{
    /** @param list<BankMode> $modes */
    public function __construct(
        public BankCode $code,
        public string $displayName,
        public ?string $logoUrl,
        public bool $enabled,
        public array $modes,
        public BankCapabilities $capabilities,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            code: BankCode::tryFromWire(Cast::nullableString($d['code'] ?? null)),
            displayName: Cast::string($d['display_name'] ?? null),
            logoUrl: Cast::nullableString($d['logo_url'] ?? null),
            enabled: Cast::bool($d['enabled'] ?? null),
            modes: array_map(
                static fn (string $m): BankMode => BankMode::tryFromWire($m),
                Cast::stringList($d, 'modes'),
            ),
            capabilities: BankCapabilities::fromArray(Cast::map($d, 'capabilities')),
        );
    }
}
