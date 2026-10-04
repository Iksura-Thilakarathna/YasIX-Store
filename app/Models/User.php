<?php

namespace App\Models;

class User extends Model
{
    public function isAdmin(): bool
    {
        return isset($this->attributes['role_name']) && strtoupper($this->attributes['role_name']) === 'ADMIN';
    }

    public function isCustomer(): bool
    {
        return isset($this->attributes['role_name']) && strtoupper($this->attributes['role_name']) === 'CUSTOMER';
    }
}
