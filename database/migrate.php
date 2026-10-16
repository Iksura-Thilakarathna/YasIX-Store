<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Database/Connection.php';

use App\Database\Connection;

try {
    $pdo = Connection::getInstance();
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        echo "[MIGRATE] Running {$filename}...\n";
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        echo "[SUCCESS] {$filename} executed successfully.\n";
    }
} catch (Throwable $e) {
    echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
