<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Database/Connection.php';

use App\Database\Connection;

try {
    $pdo = Connection::getInstance();
    $migrationFile = __DIR__ . '/migrations/2026_10_13_day5_order_warranty.sql';

    if (!file_exists($migrationFile)) {
        throw new RuntimeException("Migration file not found: {$migrationFile}");
    }

    $sql = file_get_contents($migrationFile);
    
    // Execute SQL script
    $pdo->exec($sql);

    echo "[SUCCESS] Day 5 Migration executed successfully.\n";
} catch (Throwable $e) {
    echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
