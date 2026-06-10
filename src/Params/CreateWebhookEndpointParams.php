<?php

declare(strict_types=1);

namespace KosovoPay\Params;

use InvalidArgumentException;
use KosovoPay\Enums\WebhookEventType;

final readonly class CreateWebhookEndpointParams
{
    /** @param non-empty-list<WebhookEventType> $enabledEvents */
    public function __construct(
        public string $url,
        public array $enabledEvents,
        public ?string $description = null,
    ) {
        if ($enabledEvents === []) {
            throw new InvalidArgumentException('enabledEvents must contain at least one event type.');
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('url must be an http or https URL.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'description' => $this->description,
            'enabled_events' => array_values(array_map(
                static fn (WebhookEventType $e): string => $e->value,
                $this->enabledEvents,
            )),
        ], static fn (mixed $v): bool => $v !== null);
    }
}
