<?php

/**
 * YasIX Store - Day 1 Architecture & Database Verification Script
 */

declare(strict_types=1);

// Register PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once dirname(__DIR__) . '/app/Helpers/Sanitizer.php';

echo "==================================================" . PHP_EOL;
echo "   YASIX STORE - DAY 1 BACKEND VERIFICATION TEST   " . PHP_EOL;
echo "==================================================" . PHP_EOL;

$errors = [];

// 1. Verify Configuration & Environment Loading
try {
    $config = require dirname(__DIR__) . '/config/config.php';
    echo "[PASS] Configuration loaded. App Name: " . $config['app']['name'] . PHP_EOL;
} catch (Throwable $e) {
    $errors[] = "Config Error: " . $e->getMessage();
    echo "[FAIL] Configuration load failed!" . PHP_EOL;
}

// 2. Verify Database Connection
try {
    $db = \App\Database\Connection::getInstance();
    echo "[PASS] PDO Database connection established successfully." . PHP_EOL;
} catch (Throwable $e) {
    $errors[] = "DB Connection Error: " . $e->getMessage();
    echo "[FAIL] Database connection failed!" . PHP_EOL;
}

// 3. Verify Database Tables Count & Schema
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "[PASS] Tables found in database: " . count($tables) . " tables." . PHP_EOL;
    echo "       Tables: " . implode(', ', $tables) . PHP_EOL;
} catch (Throwable $e) {
    $errors[] = "Schema Error: " . $e->getMessage();
    echo "[FAIL] Table query failed!" . PHP_EOL;
}

// 4. Verify Seed Data (Users, Categories, Products)
try {
    $userRepo = new \App\Repositories\UserRepository();
    $admin = $userRepo->findByEmail('admin@yasix.com');
    if ($admin && password_verify('AdminPass123!', $admin['password_hash'])) {
        echo "[PASS] Admin seed user found and password verified (Role: " . $admin['role_name'] . ")." . PHP_EOL;
    } else {
        $errors[] = "Admin verification failed!";
        echo "[FAIL] Admin seed verification failed!" . PHP_EOL;
    }

    $customer = $userRepo->findByEmail('customer@yasix.com');
    if ($customer && password_verify('CustomerPass123!', $customer['password_hash'])) {
        echo "[PASS] Customer seed user found and password verified (Role: " . $customer['role_name'] . ")." . PHP_EOL;
    } else {
        $errors[] = "Customer verification failed!";
        echo "[FAIL] Customer seed verification failed!" . PHP_EOL;
    }
} catch (Throwable $e) {
    $errors[] = "User Verification Error: " . $e->getMessage();
    echo "[FAIL] Seed user query failed!" . PHP_EOL;
}

// 5. Verify Product & Category Repositories
try {
    $productRepo = new \App\Repositories\ProductRepository();
    $featured = $productRepo->getFeaturedProducts(5);
    echo "[PASS] Featured products queried from DB: " . count($featured) . " items found." . PHP_EOL;
    foreach ($featured as $p) {
        echo "       - [" . $p['category_name'] . "] " . $p['name'] . " ($" . number_format((float)$p['price'], 2) . ") | Stock: " . $p['stock_quantity'] . " | Warranty: " . $p['warranty_months'] . " months" . PHP_EOL;
    }
} catch (Throwable $e) {
    $errors[] = "Product Repository Error: " . $e->getMessage();
    echo "[FAIL] Product repository query failed!" . PHP_EOL;
}

// 6. Verify Service Layer Integration
try {
    $productService = new \App\Services\ProductService();
    $homeProducts = $productService->getHomeFeaturedProducts();
    echo "[PASS] ProductService returned " . count($homeProducts) . " products for Home View." . PHP_EOL;
} catch (Throwable $e) {
    $errors[] = "ProductService Error: " . $e->getMessage();
    echo "[FAIL] ProductService failed!" . PHP_EOL;
}

// Summary
echo "==================================================" . PHP_EOL;
if (empty($errors)) {
    echo " RESULT: ALL DAY 1 VERIFICATION CHECKS PASSED 🚀" . PHP_EOL;
} else {
    echo " RESULT: VERIFICATION FAILED WITH " . count($errors) . " ERRORS ❌" . PHP_EOL;
    foreach ($errors as $err) {
        echo " - " . $err . PHP_EOL;
    }
}
echo "==================================================" . PHP_EOL;
