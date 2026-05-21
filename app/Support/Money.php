<?php

namespace App\Support;

final class Money
{
    public static function normalize(int|float|string $amount): string
    {
        $value = trim((string) $amount);
        $value = str_replace(',', '.', $value);

        if ($value === '' || ! is_numeric($value)) {
            throw new \InvalidArgumentException('Monto monetario inválido.');
        }

        return number_format((float) $value, 2, '.', '');
    }

    public static function multiply(int|float|string $amount, int|float|string $multiplier): string
    {
        return number_format(round((float) self::normalize($amount) * (float) self::normalize($multiplier), 2), 2, '.', '');
    }

    public static function add(int|float|string $left, int|float|string $right): string
    {
        return number_format((float) self::normalize($left) + (float) self::normalize($right), 2, '.', '');
    }

    public static function subtract(int|float|string $left, int|float|string $right): string
    {
        $difference = self::toCents($left) - self::toCents($right);

        return self::fromCents($difference);
    }

    public static function absolute(int|float|string $amount): string
    {
        return self::fromCents(abs(self::toCents($amount)));
    }

    public static function isNegative(int|float|string $amount): bool
    {
        return self::toCents($amount) < 0;
    }

    public static function isPositive(int|float|string $amount): bool
    {
        return self::toCents($amount) > 0;
    }

    public static function isLessThan(int|float|string $left, int|float|string $right): bool
    {
        return self::toCents($left) < self::toCents($right);
    }

    public static function isGreaterThan(int|float|string $left, int|float|string $right): bool
    {
        return self::toCents($left) > self::toCents($right);
    }

    public static function toFloat(int|float|string $amount): float
    {
        return (float) self::normalize($amount);
    }

    public static function toCents(int|float|string $amount): int
    {
        $normalized = self::normalize($amount);
        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');
        [$major, $minor] = array_pad(explode('.', $normalized, 2), 2, '00');

        $cents = ((int) $major * 100) + (int) str_pad(substr($minor, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);

        return ($negative ? '-' : '').number_format($absolute / 100, 2, '.', '');
    }
}
