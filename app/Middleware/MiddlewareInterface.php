<?php

namespace App\Middleware;

interface MiddlewareInterface
{
    public function handle(array $params = []): bool;
}
