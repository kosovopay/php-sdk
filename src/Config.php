<?php

declare(strict_types=1);

namespace KosovoPay;

use InvalidArgumentException;

/**
 * Immutable client configuration. Built once by {@see KosovoPay} and shared by
 * the connector. Nothing here is mutated after construction.
 */
final readonly class Config
{
    public const DEFAULT_BASE_URL = 'https://api.kosovo.sh';

    public const DEFAULT_API_VERSION = '2026-06-01';

    public function __construct(
        public string $apiKey,
        public string $baseUrl = self::DEFAULT_BASE_URL,
        public string $apiVersion = self::DEFAULT_API_VERSION,
        public int $connectTimeout = 10,
        public int $requestTimeout = 30,
        public int $maxRetries = 3,
        public int $retryIntervalMs = 500,
    ) {
        if ($apiKey === '') {
            throw new InvalidArgumentException('A KosovoPay API key is required.');
        }
    }
}
