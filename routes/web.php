<?php

use App\Controllers\HomeController;
use App\Controllers\ErrorController;
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\ProductController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/home' => [HomeController::class, 'index'],
        '/products' => [ProductController::class, 'index'],
        '/products/{slug}' => [ProductController::class, 'detail'],
        '/product/{slug}' => [ProductController::class, 'detail'],
        '/login' => [AuthController::class, 'loginForm'],
        '/register' => [AuthController::class, 'registerForm'],
        '/account' => [AccountController::class, 'index', [AuthMiddleware::class]],
    ],
    'POST' => [
        '/login' => [AuthController::class, 'login'],
        '/register' => [AuthController::class, 'register'],
        '/logout' => [AuthController::class, 'logout', [AuthMiddleware::class]],
        '/account/profile' => [AccountController::class, 'updateProfile', [AuthMiddleware::class]],
        '/account/password' => [AccountController::class, 'changePassword', [AuthMiddleware::class]],
    ]
];
