<?php

// Day 4 Delivery Fee Calculator Verification Suite
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

use App\Services\DeliveryCalculatorService;
use App\Repositories\DeliveryRuleRepository;

echo "==================================================\n";
echo "   YASIX STORE - DAY 4 DELIVERY CALCULATOR TEST   \n";
echo "==================================================\n";

$calcService = new DeliveryCalculatorService();
$ruleRepo = new DeliveryRuleRepository();

// 1. Verify Active Rules Retrieval
$activeRules = $calcService->getActiveRules();
if (count($activeRules) >= 3) {
    echo "[PASS] Active delivery rules retrieved (Count: " . count($activeRules) . ").\n";
} else {
    echo "[FAIL] Failed to retrieve active delivery rules.\n";
    exit(1);
}

// 2. Test Volumetric Weight Calculation (Volumetric > Actual)
// Item: Headset, Actual: 0.45 kg, Dim: 20x18x10 cm
// Volumetric: (20*18*10)/5000 = 0.72 kg -> Chargeable: 0.72 kg
$itemWeight = $calcService->calculateItemWeight(0.45, 20.0, 18.0, 10.0, 5000);
if ($itemWeight['volumetric_weight_kg'] == 0.72 && $itemWeight['chargeable_weight_kg'] == 0.72) {
    echo "[PASS] Volumetric weight calculation verified where volumetric > actual (0.72 kg).\n";
} else {
    echo "[FAIL] Volumetric weight calculation incorrect: " . print_r($itemWeight, true) . "\n";
    exit(1);
}

// 3. Test Actual Weight Greater Than Volumetric Weight
// Item: Solid Heavy Plate, Actual: 5.0 kg, Dim: 10x10x10 cm
// Volumetric: (10*10*10)/5000 = 0.20 kg -> Chargeable: 5.0 kg
$heavyWeight = $calcService->calculateItemWeight(5.0, 10.0, 10.0, 10.0, 5000);
if ($heavyWeight['chargeable_weight_kg'] == 5.0) {
    echo "[PASS] Actual weight verified when actual > volumetric (5.0 kg).\n";
} else {
    echo "[FAIL] Actual weight comparison failed.\n";
    exit(1);
}

// 4. Test Single Low-Weight Item Delivery Calculation (Western Province: Base 350, Included 1kg, Extra 50/kg)
// Headset: 0.72 kg -> rounded to 1.0 kg -> within 1.0kg allowance -> Total fee: 350.00 LKR
$singleItemCart = [
    [
        'weight' => 0.45,
        'package_length' => 20.0,
        'package_width' => 18.0,
        'package_height' => 10.0,
        'quantity' => 1
    ]
];
$deliveryResult = $calcService->calculateDelivery($singleItemCart, 1);
if ($deliveryResult['success'] && $deliveryResult['total_delivery_fee'] == 350.00) {
    echo "[PASS] Single item delivery fee verified within included weight ($350.00).\n";
} else {
    echo "[FAIL] Base delivery calculation failed: " . print_r($deliveryResult, true) . "\n";
    exit(1);
}

// 5. Test Multiple Items & Extra Weight Charge
// 2x Neural Rig: Actual 15.5kg each, 55x30x60 cm (volumetric: 19.8kg each)
// Total raw chargeable = 19.8 * 2 = 39.6 kg -> rounded to 40.0 kg.
// Extra weight above 1kg = 39 kg.
// Outstation Central (Base: 450, Extra: 75/kg) -> 450 + (39 * 75) = 450 + 2925 = 3375.00
$multiItemCart = [
    [
        'weight' => 15.5,
        'package_length' => 55.0,
        'package_width' => 30.0,
        'package_height' => 60.0,
        'quantity' => 2
    ]
];
$heavyOrderResult = $calcService->calculateDelivery($multiItemCart, 2);
if ($heavyOrderResult['success'] && $heavyOrderResult['total_delivery_fee'] == 3375.00) {
    echo "[PASS] Multiple heavy items delivery fee with extra weight verified ($3,375.00).\n";
} else {
    echo "[FAIL] Heavy order calculation failed: " . print_r($heavyOrderResult, true) . "\n";
    exit(1);
}

// 6. Test Invalid / Inactive Delivery Rule
$invalidRule = $calcService->calculateDelivery($singleItemCart, 9999);
if (!$invalidRule['success']) {
    echo "[PASS] Invalid delivery region correctly rejected with validation message.\n";
} else {
    echo "[FAIL] Invalid delivery region was allowed.\n";
    exit(1);
}

// 7. Test Zero and Negative Dimension Safety
$zeroWeight = $calcService->calculateItemWeight(-5.0, -10.0, 10.0, 10.0, 5000);
if ($zeroWeight['actual_weight_kg'] >= 0.0 && $zeroWeight['chargeable_weight_kg'] >= 0.0) {
    echo "[PASS] Negative dimensions and weights handled safely.\n";
} else {
    echo "[FAIL] Negative weight handling failed.\n";
    exit(1);
}

echo "==================================================\n";
echo " RESULT: ALL COMMIT 2 DELIVERY TESTS PASSED 🚀    \n";
echo "==================================================\n";
