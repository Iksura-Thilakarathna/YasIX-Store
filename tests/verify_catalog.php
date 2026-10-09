<?php

// Day 3 Product Catalog Verification Suite
chdir(__DIR__ . '/..');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\ProductService;
use App\Repositories\ProductRepository;

echo "==================================================\n";
echo "   YASIX STORE - DAY 3 CATALOG VERIFICATION TEST  \n";
echo "==================================================\n";

$productService = new ProductService();
$productRepo = new ProductRepository();

// 1. Verify Catalog Default Query
$catalog = $productService->getCatalog([]);
if (!empty($catalog['products']) && $catalog['pagination']['total_products'] >= 14) {
    echo "[PASS] Catalog default query returned " . $catalog['pagination']['total_products'] . " active products from MySQL.\n";
} else {
    echo "[FAIL] Catalog query failed or returned unexpected product count.\n";
    exit(1);
}

// 2. Verify Keyword Search
$searchResult = $productService->getCatalog(['q' => 'RTX 5090']);
if (!empty($searchResult['products'])) {
    echo "[PASS] Keyword search ('RTX 5090') returned " . count($searchResult['products']) . " matching items.\n";
} else {
    echo "[FAIL] Keyword search failed for 'RTX 5090'.\n";
    exit(1);
}

// 3. Verify Category Filtering
$categoryResult = $productService->getCatalog(['category' => 'quantum-gear']);
if (!empty($categoryResult['products'])) {
    foreach ($categoryResult['products'] as $p) {
        if ($p['category_slug'] !== 'quantum-gear') {
            echo "[FAIL] Category filter returned item outside target category.\n";
            exit(1);
        }
    }
    echo "[PASS] Category filter ('quantum-gear') returned " . count($categoryResult['products']) . " verified items.\n";
} else {
    echo "[FAIL] Category filter returned no items.\n";
    exit(1);
}

// 4. Verify Brand & Condition Filtering
$brandResult = $productService->getCatalog(['brand' => 'Intel', 'condition' => 'NEW']);
if (!empty($brandResult['products'])) {
    echo "[PASS] Multi-filter ('Intel' + 'NEW') returned " . count($brandResult['products']) . " items.\n";
} else {
    echo "[FAIL] Brand and Condition filter combination failed.\n";
    exit(1);
}

// 5. Verify Price Range Filtering
$priceResult = $productService->getCatalog(['min_price' => 200, 'max_price' => 500]);
if (!empty($priceResult['products'])) {
    foreach ($priceResult['products'] as $p) {
        if ($p['price'] < 200 || $p['price'] > 500) {
            echo "[FAIL] Product price outside requested range ($200-$500): $" . $p['price'] . "\n";
            exit(1);
        }
    }
    echo "[PASS] Price range filter ($200 - $500) verified.\n";
} else {
    echo "[FAIL] Price range filter returned no items.\n";
    exit(1);
}

// 6. Verify In-Stock Only Filtering
$inStockResult = $productService->getCatalog(['in_stock' => '1']);
foreach ($inStockResult['products'] as $p) {
    if (!$p['is_available']) {
        echo "[FAIL] In-stock filter included out of stock product: " . $p['name'] . "\n";
        exit(1);
    }
}
echo "[PASS] In-stock only filter verified.\n";

// 7. Verify Safe Sorting
$sortedAsc = $productService->getCatalog(['sort' => 'price_asc']);
$sortedDesc = $productService->getCatalog(['sort' => 'price_desc']);
if ($sortedAsc['products'][0]['price'] <= $sortedAsc['products'][1]['price'] &&
    $sortedDesc['products'][0]['price'] >= $sortedDesc['products'][1]['price']) {
    echo "[PASS] Safe price sorting (asc / desc) verified.\n";
} else {
    echo "[FAIL] Safe price sorting failed.\n";
    exit(1);
}

// 8. Verify Pagination Math
$page1 = $productService->getCatalog(['page' => 1]);
$page2 = $productService->getCatalog(['page' => 2]);
if ($page1['pagination']['total_pages'] > 1 && $page1['products'][0]['id'] !== $page2['products'][0]['id']) {
    echo "[PASS] Pagination page 1 and page 2 items are distinct.\n";
} else {
    echo "[FAIL] Pagination failed.\n";
    exit(1);
}

// 9. Verify Product Detail Retrieval
$detail = $productService->getProductBySlug('yasix-neural-rig-rtx-5090');
if ($detail && $detail['name'] === 'YasIX Neural Rig RTX 5090' && !empty($detail['related_products'])) {
    echo "[PASS] Product detail retrieval by slug verified with related hardware.\n";
} else {
    echo "[FAIL] Product detail retrieval failed.\n";
    exit(1);
}

// 10. Verify Invalid Slug Returns Null
$invalidDetail = $productService->getProductBySlug('non-existent-cyber-rig-9999');
if ($invalidDetail === null) {
    echo "[PASS] Invalid product slug safely returned null (404 state).\n";
} else {
    echo "[FAIL] Invalid product slug did not return null.\n";
    exit(1);
}

echo "==================================================\n";
echo " RESULT: ALL DAY 3 CATALOG VERIFICATION CHECKS PASSED 🚀 \n";
echo "==================================================\n";
