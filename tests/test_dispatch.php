<?php

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

ob_start();
require __DIR__ . '/../public/index.php';
$output = ob_get_clean();

if (str_contains($output, 'YASIX') || str_contains($output, 'YAS')) {
    echo "[PASS] Front Controller successfully rendered homepage (" . strlen($output) . " bytes).\n";
} else {
    echo "[FAIL] Homepage output missing expected elements. Output snippet:\n" . substr($output, 0, 500) . "\n";
    exit(1);
}

// Test /login GET dispatch
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login';
ob_start();
require __DIR__ . '/../public/index.php';
$loginOutput = ob_get_clean();

if (str_contains($loginOutput, 'System Access') && str_contains($loginOutput, 'AUTHENTICATE')) {
    echo "[PASS] Front Controller successfully rendered /login view (" . strlen($loginOutput) . " bytes).\n";
} else {
    echo "[FAIL] /login output missing expected elements.\n";
    exit(1);
}

// Test /register GET dispatch
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/register';
ob_start();
require __DIR__ . '/../public/index.php';
$registerOutput = ob_get_clean();

if (str_contains($registerOutput, 'Initialize Node') && str_contains($registerOutput, 'REGISTER ACCOUNT')) {
    echo "[PASS] Front Controller successfully rendered /register view (" . strlen($registerOutput) . " bytes).\n";
} else {
    echo "[FAIL] /register output missing expected elements.\n";
    exit(1);
}
