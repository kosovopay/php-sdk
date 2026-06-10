<?php

declare(strict_types=1);

namespace KosovoPay\Internal;

/**
 * Type-narrowing coercion for decoded JSON (which is `mixed` at every leaf).
 * Each helper guards the type before converting, so a malformed or unexpected
 * value degrades to a sane default instead of a TypeError — and static analysis
 * sees a definite type, not a blind cast.
 */
final class Cast
{
    /** @return array<array-key, mixed> */
    public static function object(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<array<array-key, mixed>>
     */
    public static function rows(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $rows = [];
        foreach ($value as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public static function string(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : $default;
    }

    public static function nullableString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : null;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function nullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    public static function bool(mixed $value, bool $default = false): bool
    {
        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    public static function map(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    public static function stringList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
