<?php

declare(strict_types=1);

namespace KosovoPay;

use JsonException;
use KosovoPay\Dto\Event;
use KosovoPay\Exceptions\WebhookSignatureException;

/**
 * Verifies inbound webhook signatures and constructs a typed Event.
 *
 * Header: `Kosovopay-Signature: t=<unix>,v1=<hex hmac-sha256>`
 * Signed payload: `"{t}.{raw_body}"` — verify against the RAW body, never a
 * re-encoded one.
 */
final class Webhook
{
    public const SIGNATURE_HEADER = 'Kosovopay-Signature';

    private const DEFAULT_TOLERANCE = 300;

    /**
     * Verify the signature and decode the raw body into a typed Event.
     *
     * @throws WebhookSignatureException on a missing/invalid/stale signature or unparseable body
     */
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): Event {
        if (! self::verify($payload, $signatureHeader, $secret, null, $tolerance)) {
            throw new WebhookSignatureException('Webhook signature verification failed.');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new WebhookSignatureException('Webhook payload is not valid JSON: '.$e->getMessage());
        }

        return Event::fromArray($decoded);
    }

    /**
     * Constant-time signature check with a timestamp-tolerance (replay) window.
     * Pass `$now` for deterministic testing; defaults to the current time.
     */
    public static function verify(
        string $payload,
        string $signatureHeader,
        string $secret,
        ?int $now = null,
        int $tolerance = self::DEFAULT_TOLERANCE,
    ): bool {
        $parts = self::parseHeader($signatureHeader);
        $timestamp = isset($parts['t']) ? (int) $parts['t'] : 0;
        $given = $parts['v1'] ?? '';

        if ($timestamp <= 0 || $given === '') {
            return false;
        }

        $now ??= time();
        if (abs($now - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $given);
    }

    /** @return array<string, string> */
    private static function parseHeader(string $header): array
    {
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            $pair = explode('=', trim($piece), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        return $parts;
    }
}
