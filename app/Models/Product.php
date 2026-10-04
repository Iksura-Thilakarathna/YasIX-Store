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
}
