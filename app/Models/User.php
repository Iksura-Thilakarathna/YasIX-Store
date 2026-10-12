<?php

namespace App\Models;

class User extends Model
{
    public function verifyPassword(string $password): bool
    {
        $hash = $this->attributes['password_hash'] ?? '';
        return !empty($hash) && password_verify($password, $hash);
    }

    public function isActive(): bool
    {
        return ($this->attributes['status'] ?? '') === 'ACTIVE';
    }

    public function isAdmin(): bool
    {
        return isset($this->attributes['role_name']) && strtoupper($this->attributes['role_name']) === 'ADMIN';
    }

    public function isCustomer(): bool
    {
        return isset($this->attributes['role_name']) && strtoupper($this->attributes['role_name']) === 'CUSTOMER';
    }
}
