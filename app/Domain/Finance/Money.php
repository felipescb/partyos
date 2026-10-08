<?php

namespace App\Domain\Finance;

use InvalidArgumentException;

final class Money
{
    public static function parse(string $input): int
    {
        $clean = preg_replace('/[^\d,.-]/', '', trim($input)) ?? '';

        if ($clean === '' || $clean === '-') {
            return 0;
        }

        $negative = str_starts_with($clean, '-');
        $clean = str_replace('-', '', $clean);

        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $parts = explode(',', $clean, 2);
            $whole = self::digits($parts[0]);
            $fraction = self::digits($parts[1]);
        } elseif (preg_match('/^(\d*)\.(\d{1,2})$/', $clean, $matches) === 1) {
            $whole = self::digits($matches[1]);
            $fraction = str_pad($matches[2], 2, '0');
        } else {
            $whole = self::digits(str_replace('.', '', $clean));
            $fraction = '0';
        }

        $cents = ((int) $whole) * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        if (strlen($fraction) > 2 && (int) substr($fraction, 2, 1) >= 5) {
            $cents++;
        }

        return $negative ? -$cents : $cents;
    }

    public static function format(int $cents, string $currency = 'BRL'): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);
        $whole = intdiv($cents, 100);
        $fraction = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $formatted = number_format($whole, 0, ',', '.').','.$fraction;

        $symbol = match ($currency) {
            'BRL' => 'R$',
            'USD' => 'US$',
            'EUR' => '€',
            default => $currency,
        };

        return ($negative ? '-' : '').$symbol.' '.$formatted;
    }

    public static function input(int $cents): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);
        $whole = number_format(intdiv($cents, 100), 0, ',', '.');
        $fraction = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$whole.','.$fraction;
    }

    public static function formatPercent(int $basisPoints): string
    {
        $negative = $basisPoints < 0;
        $basisPoints = abs($basisPoints);
        $whole = intdiv($basisPoints, 100);
        $fraction = $basisPoints % 100;
        $text = $fraction === 0
            ? (string) $whole
            : $whole.','.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$text.'%';
    }

    public static function portion(int $amount, int $basisPoints): int
    {
        $negative = ($amount < 0) !== ($basisPoints < 0);
        $value = intdiv(abs($amount) * abs($basisPoints) + 5000, 10000);

        return $negative ? -$value : $value;
    }

    public static function ceilDiv(int $numerator, int $denominator): int
    {
        if ($denominator <= 0) {
            throw new InvalidArgumentException('O divisor precisa ser positivo.');
        }

        if ($numerator <= 0) {
            return 0;
        }

        return intdiv($numerator + $denominator - 1, $denominator);
    }

    private static function digits(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return $digits === '' ? '0' : $digits;
    }
}
