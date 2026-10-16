<?php

declare(strict_types=1);

/**
 * Global Helper Functions
 */

if (!function_exists('e')) {
    function e(?string $string): string {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency(float $amount, string $currencySymbol = 'LKR '): string {
        return $currencySymbol . number_format($amount, 2);
    }
}

if (!function_exists('formatLKR')) {
    function formatLKR(float $amount): string {
        return 'LKR ' . number_format($amount, 2);
    }
}
