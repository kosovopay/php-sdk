<?php

declare(strict_types=1);

namespace KosovoPay\Dto;

use DateTimeImmutable;
use KosovoPay\Enums\WebhookEventType;
use KosovoPay\Internal\Cast;

/**
 * A webhook event. `data.object` is the affected resource as a raw map — narrow
 * on {@see $type}: payment.* carries a Payment, refund.* carries a Refund.
 */
final readonly class Event
{
    /**
     * @param  array<string, mixed>  $data  { object, previous_attributes? }
     * @param  array<string, mixed>  $object  the affected resource
     * @param  array<string, mixed>|null  $previousAttributes
     */
    public function __construct(
        public string $id,
        public WebhookEventType $type,
        public int $created,
        public bool $livemode,
        public string $apiVersion,
        public array $data,
        public array $object,
        public ?array $previousAttributes,
    ) {}

    /** @param array<array-key, mixed> $d */
    public static function fromArray(array $d): self
    {
        $data = Cast::map($d, 'data');

        return new self(
            id: Cast::string($d['id'] ?? null),
            type: WebhookEventType::tryFromWire(Cast::nullableString($d['type'] ?? null)),
            created: Cast::int($d['created'] ?? null),
            livemode: Cast::bool($d['livemode'] ?? null),
            apiVersion: Cast::string($d['api_version'] ?? null),
            data: $data,
            object: is_array($data['object'] ?? null) ? self::toStringKeyed($data['object']) : [],
            previousAttributes: is_array($data['previous_attributes'] ?? null)
                ? self::toStringKeyed($data['previous_attributes'])
                : null,
        );
    }

    /** Hydrate `data.object` as a Payment (only valid for payment.* events). */
    public function asPayment(): Payment
    {
        return Payment::fromArray($this->object);
    }

    /** Hydrate `data.object` as a Refund (only valid for refund.* events). */
    public function asRefund(): Refund
    {
        return Refund::fromArray($this->object);
    }

    public function createdAt(): DateTimeImmutable
    {
        return (new DateTimeImmutable)->setTimestamp($this->created);
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<string, mixed>
     */
    private static function toStringKeyed(array $value): array
    {
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }
}
