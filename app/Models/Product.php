<?php

namespace App\Models;

class Product extends Model
{
    public function getFormattedPrice(): string
    {
        return '$' . number_format((float)($this->attributes['price'] ?? 0), 2);
    }

    public function isAvailable(): bool
    {
        return ($this->attributes['status'] ?? '') === 'AVAILABLE' && ($this->attributes['stock_quantity'] ?? 0) > 0;
    }

    public function getConditionLabel(): string
    {
        $cond = strtoupper($this->attributes['condition'] ?? 'NEW');
        return match ($cond) {
            'REFURBISHED' => 'Refurbished',
            'CUSTOM_MOD' => 'Custom Mod',
            default => 'Brand New'
        };
    }

    public function getWarrantyLabel(): string
    {
        $months = (int)($this->attributes['warranty_months'] ?? 12);
        if ($months >= 12 && $months % 12 === 0) {
            $years = $months / 12;
            return $years . ($years === 1 ? ' Year Warranty' : ' Years Warranty');
        }
        return $months . ' Months Warranty';
    }

    public function getCategoryIcon(): string
    {
        return $this->attributes['icon_class'] ?? $this->attributes['icon'] ?? 'fa-solid fa-microchip';
    }
}
