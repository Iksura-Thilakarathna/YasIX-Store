<?php

use App\Controllers\HomeController;
use App\Controllers\ErrorController;
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\OrderController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

return [
    'GET' => [
        '/' => [HomeController::class, 'index'],
        '/home' => [HomeController::class, 'index'],
        '/products' => [ProductController::class, 'index'],
        '/products/{slug}' => [ProductController::class, 'detail'],
        '/product/{slug}' => [ProductController::class, 'detail'],
        '/cart' => [CartController::class, 'index', [AuthMiddleware::class]],
        '/checkout' => [CheckoutController::class, 'index', [AuthMiddleware::class]],
        '/checkout/success/{order_number}' => [CheckoutController::class, 'success', [AuthMiddleware::class]],
        '/orders' => [OrderController::class, 'index', [AuthMiddleware::class]],
        '/account/orders' => [OrderController::class, 'index', [AuthMiddleware::class]],
        '/orders/{order_number}' => [OrderController::class, 'detail', [AuthMiddleware::class]],
        '/orders/{order_number}/track' => [OrderController::class, 'track', [AuthMiddleware::class]],
        '/login' => [AuthController::class, 'loginForm'],
        '/register' => [AuthController::class, 'registerForm'],
        '/account' => [AccountController::class, 'index', [AuthMiddleware::class]],
    ],
    'POST' => [
        '/cart/add' => [CartController::class, 'add'],
        '/cart/update' => [CartController::class, 'update', [AuthMiddleware::class]],
        '/cart/remove' => [CartController::class, 'remove', [AuthMiddleware::class]],
        '/cart/clear' => [CartController::class, 'clear', [AuthMiddleware::class]],
        '/checkout/place-order' => [CheckoutController::class, 'placeOrder', [AuthMiddleware::class]],
        '/login' => [AuthController::class, 'login'],
        '/register' => [AuthController::class, 'register'],
        '/logout' => [AuthController::class, 'logout', [AuthMiddleware::class]],
        '/account/profile' => [AccountController::class, 'updateProfile', [AuthMiddleware::class]],
        '/account/password' => [AccountController::class, 'changePassword', [AuthMiddleware::class]],
    ]
];
