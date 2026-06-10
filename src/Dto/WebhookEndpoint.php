<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use KosovoPay\Enums\BankMode;
use KosovoPay\Enums\WebhookEventType;
use KosovoPay\Internal\Cast;

final readonly class WebhookEndpoint
{
    /** @param list<WebhookEventType> $enabledEvents */
    public function __construct(
        public string $id,
        public string $url,
        public ?string $description,
        public array $enabledEvents,
        public string $status,
        public BankMode $mode,
        public ?int $created,
        public ?string $secret,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        return new self(
            id: Cast::string($d['id'] ?? null),
            url: Cast::string($d['url'] ?? null),
            description: Cast::nullableString($d['description'] ?? null),
            enabledEvents: array_map(
                static fn (string $e): WebhookEventType => WebhookEventType::tryFromWire($e),
                Cast::stringList($d, 'enabled_events'),
            ),
            status: Cast::string($d['status'] ?? null, 'enabled'),
            mode: BankMode::tryFromWire(Cast::nullableString($d['mode'] ?? null)),
            created: Cast::nullableInt($d['created'] ?? null),
            secret: Cast::nullableString($d['secret'] ?? null),
        );
    }
}
