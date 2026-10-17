<?php

use App\Controllers\HomeController;
use App\Controllers\ErrorController;
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\OrderController;
use App\Controllers\WarrantyController;
use App\Controllers\AdminController;
use App\Controllers\AdminProductController;
use App\Controllers\AdminCategoryController;
use App\Controllers\AdminInventoryController;
use App\Controllers\AdminOrderController;
use App\Controllers\AdminCustomerController;
use App\Controllers\AdminWarrantyController;
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
        '/warranties' => [WarrantyController::class, 'index', [AuthMiddleware::class]],
        '/account/warranties' => [WarrantyController::class, 'index', [AuthMiddleware::class]],
        '/warranties/claims' => [WarrantyController::class, 'claims', [AuthMiddleware::class]],
        '/account/warranty-claims' => [WarrantyController::class, 'claims', [AuthMiddleware::class]],
        '/warranties/claim/new/{order_item_id}' => [WarrantyController::class, 'createForm', [AuthMiddleware::class]],
        '/warranties/claim/{claim_number}' => [WarrantyController::class, 'detail', [AuthMiddleware::class]],
        '/login' => [AuthController::class, 'loginForm'],
        '/register' => [AuthController::class, 'registerForm'],
        '/account' => [AccountController::class, 'index', [AuthMiddleware::class]],
        
        // Admin Core & Catalog Routes
        '/admin' => [AdminController::class, 'dashboard', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/login' => [AdminController::class, 'loginForm'],
        '/admin/products' => [AdminProductController::class, 'index', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/products/create' => [AdminProductController::class, 'create', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/products/{id}/edit' => [AdminProductController::class, 'edit', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/categories' => [AdminCategoryController::class, 'index', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/inventory' => [AdminInventoryController::class, 'index', [RoleMiddleware::class => ['ADMIN']]],

        // Admin Order, Payment, Customer & Warranty Routes
        '/admin/orders' => [AdminOrderController::class, 'index', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/orders/{order_number}' => [AdminOrderController::class, 'detail', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/payments' => [AdminOrderController::class, 'payments', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/customers' => [AdminCustomerController::class, 'index', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/customers/{id}' => [AdminCustomerController::class, 'detail', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/warranties' => [AdminWarrantyController::class, 'index', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/warranties/{id}' => [AdminWarrantyController::class, 'detail', [RoleMiddleware::class => ['ADMIN']]],
    ],
    'POST' => [
        '/cart/add' => [CartController::class, 'add'],
        '/cart/update' => [CartController::class, 'update', [AuthMiddleware::class]],
        '/cart/remove' => [CartController::class, 'remove', [AuthMiddleware::class]],
        '/cart/clear' => [CartController::class, 'clear', [AuthMiddleware::class]],
        '/checkout/place-order' => [CheckoutController::class, 'placeOrder', [AuthMiddleware::class]],
        '/orders/{order_number}/cancel' => [OrderController::class, 'cancel', [AuthMiddleware::class]],
        '/warranties/claim' => [WarrantyController::class, 'submit', [AuthMiddleware::class]],
        '/login' => [AuthController::class, 'login'],
        '/register' => [AuthController::class, 'register'],
        '/logout' => [AuthController::class, 'logout', [AuthMiddleware::class]],
        '/account/profile' => [AccountController::class, 'updateProfile', [AuthMiddleware::class]],
        '/account/password' => [AccountController::class, 'changePassword', [AuthMiddleware::class]],
        '/admin/login' => [AdminController::class, 'login'],
        '/admin/logout' => [AdminController::class, 'logout', [RoleMiddleware::class => ['ADMIN']]],

        // Admin Product, Category & Inventory POST actions
        '/admin/products/create' => [AdminProductController::class, 'store', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/products/{id}/update' => [AdminProductController::class, 'update', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/products/{id}/toggle-status' => [AdminProductController::class, 'toggleStatus', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/categories/create' => [AdminCategoryController::class, 'store', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/categories/{id}/update' => [AdminCategoryController::class, 'update', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/categories/{id}/toggle-status' => [AdminCategoryController::class, 'toggleStatus', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/categories/{id}/delete' => [AdminCategoryController::class, 'delete', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/inventory/adjust' => [AdminInventoryController::class, 'adjust', [RoleMiddleware::class => ['ADMIN']]],

        // Admin Order, Payment, Customer & Warranty POST actions
        '/admin/orders/{order_number}/status' => [AdminOrderController::class, 'updateStatus', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/orders/{order_number}/collect-payment' => [AdminOrderController::class, 'recordPayment', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/customers/{id}/toggle-status' => [AdminCustomerController::class, 'toggleStatus', [RoleMiddleware::class => ['ADMIN']]],
        '/admin/warranties/{id}/decide' => [AdminWarrantyController::class, 'decide', [RoleMiddleware::class => ['ADMIN']]],
    ]
];
