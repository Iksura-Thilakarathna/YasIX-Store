<?php

namespace App\Helpers;

class Sanitizer
{
    public static function string(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function email(?string $email): string
    {
        if ($email === null) {
            return '';
        }
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function int(mixed $value): int
    {
        return (int)$value;
    }

    public static function float(mixed $value): float
    {
        return (float)$value;
    }
}

// Global XSS escaping helper function
if (!function_exists('e')) {
    function e(?string $string): string {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Global Currency formatting helper function
if (!function_exists('formatCurrency')) {
    function formatCurrency(float $amount, string $currencySymbol = 'LKR '): string {
        return $currencySymbol . number_format($amount, 2);
    }
}
