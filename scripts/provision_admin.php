<?php

declare(strict_types=1);

/**
 * YasIX Store - Safe Administrator Provisioning CLI Tool
 * Usage:
 *   Interactive: php scripts/provision_admin.php
 *   Direct:      php scripts/provision_admin.php "Admin Name" "admin@example.com" "SecurePass123!" "+94770000000"
 */

require_once dirname(__DIR__) . '/app/Database/Connection.php';

use App\Database\Connection;

echo "========================================================\n";
echo "   YASIX STORE - SECURE ADMINISTRATOR PROVISIONING TOOL  \n";
echo "========================================================\n\n";

if (php_sapi_name() !== 'cli') {
    die("Error: This tool can only be executed via the command line interface.\n");
}

$pdo = Connection::getInstance();

$name = $argv[1] ?? null;
$email = $argv[2] ?? null;
$password = $argv[3] ?? null;
$phone = $argv[4] ?? '+94770000000';

if (!$name) {
    echo "Enter Administrator Full Name: ";
    $name = trim(fgets(STDIN) ?: '');
}

if (!$email) {
    echo "Enter Administrator Email: ";
    $email = trim(fgets(STDIN) ?: '');
}

if (!$password) {
    echo "Enter Secure Password (min 8 characters): ";
    $password = trim(fgets(STDIN) ?: '');
}

if (empty($name) || strlen($name) < 2) {
    echo "[ERROR] Name must be at least 2 characters.\n";
    exit(1);
}

$email = strtolower(trim($email));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "[ERROR] Invalid email address format: {$email}\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "[ERROR] Password must be at least 8 characters long for security.\n";
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id, role_id, email, name FROM users WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET role_id = 1, 
                name = :name, 
                password_hash = :hash, 
                phone = :phone, 
                status = 'ACTIVE', 
                updated_at = NOW() 
            WHERE id = :id
        ");
        $updateStmt->execute([
            'name' => $name,
            'hash' => $passwordHash,
            'phone' => $phone,
            'id' => $existing['id']
        ]);
        echo "[SUCCESS] Existing account (#{$existing['id']}) upgraded/updated to ADMINISTRATOR privileges.\n";
        echo "Email: {$email}\n";
        echo "Status: ACTIVE\n";
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (role_id, name, email, password_hash, phone, status, created_at, updated_at) 
            VALUES (1, :name, :email, :hash, :phone, 'ACTIVE', NOW(), NOW())
        ");
        $insertStmt->execute([
            'name' => $name,
            'email' => $email,
            'hash' => $passwordHash,
            'phone' => $phone
        ]);
        $newId = $pdo->lastInsertId();
        echo "[SUCCESS] New ADMINISTRATOR account (#{$newId}) created successfully.\n";
        echo "Email: {$email}\n";
        echo "Status: ACTIVE\n";
    }
    echo "Access Portal: /admin/login\n";
    echo "========================================================\n";
} catch (Throwable $e) {
    echo "[ERROR] Database error during provisioning: " . $e->getMessage() . "\n";
    exit(1);
}
