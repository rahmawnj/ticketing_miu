<?php

namespace App\Support;

class PaymentMethod
{
    public const CASH = 'cash';
    public const TAP = 'tap';
    public const QRIS = 'qris';
    public const DEBIT = 'debit';
    public const KREDIT = 'kredit';
    public const TRANSFER = 'transfer';
    public const LAIN_LAIN = 'lain-lain';

    private const CORE_ORDER = [
        self::QRIS,
        self::DEBIT,
        self::KREDIT,
        self::TRANSFER,
        self::LAIN_LAIN,
    ];

    private const LABELS = [
        self::CASH => 'Tunai',
        self::TAP => 'Emoney (Tap)',
        self::QRIS => 'QRIS',
        self::DEBIT => 'Debit',
        self::KREDIT => 'Kredit',
        self::TRANSFER => 'Transfer',
        self::LAIN_LAIN => 'Lainnya',
    ];

    private const ALIASES = [
        'qr' => self::QRIS,
        'credit' => self::KREDIT,
        'credit card' => self::KREDIT,
        'kartu kredit' => self::KREDIT,
    ];

    public static function coreOrder(): array
    {
        return self::CORE_ORDER;
    }

    public static function normalize(?string $method): string
    {
        $normalized = strtolower(trim((string) $method));

        if ($normalized === '') {
            return $normalized;
        }

        return self::ALIASES[$normalized] ?? $normalized;
    }

    public static function options(bool $includeCash = false, bool $includeTap = false): array
    {
        $options = [];

        if ($includeCash) {
            $options[self::CASH] = self::LABELS[self::CASH];
        }

        if ($includeTap) {
            $options[self::TAP] = self::LABELS[self::TAP];
        }

        foreach (self::CORE_ORDER as $method) {
            $options[$method] = self::LABELS[$method];
        }

        return $options;
    }

    public static function validationValues(bool $includeAliases = true): array
    {
        $values = array_keys(self::LABELS);

        if ($includeAliases) {
            $values = array_merge($values, array_keys(self::ALIASES));
        }

        return array_values(array_unique($values));
    }

    public static function coreValidationValues(bool $includeAliases = true): array
    {
        $values = self::CORE_ORDER;

        if ($includeAliases) {
            foreach (self::ALIASES as $alias => $canonical) {
                if (in_array($canonical, self::CORE_ORDER, true)) {
                    $values[] = $alias;
                }
            }
        }

        return array_values(array_unique($values));
    }

    public static function displayLabel(?string $method): string
    {
        $normalized = self::normalize($method);

        if ($normalized === '') {
            return '-';
        }

        if (isset(self::LABELS[$normalized])) {
            return self::LABELS[$normalized];
        }

        return ucwords(str_replace(['-', '_'], ' ', $normalized));
    }

    public static function displayLabelUpper(?string $method): string
    {
        return strtoupper(self::displayLabel($method));
    }
}
