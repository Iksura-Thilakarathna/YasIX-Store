<?php

use App\Controllers\HomeController;
use App\Controllers\ErrorController;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/home' => [HomeController::class, 'index'],
    ],
    'POST' => [
        // Submitting routes for Day 2+ (auth, cart, etc.)
    ]
];
