<?php
/**
 * database/migrations/009_orders_enhancements.php
 * Adds price_unit and updated_at to orders table
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

echo "Running migration 009: Orders enhancements...\n";

try {
    $pdo = getDB();

    echo "  - Adding price_unit and updated_at to orders... ";
    $pdo->exec("ALTER TABLE orders
        ADD COLUMN IF NOT EXISTS price_unit VARCHAR(10) DEFAULT NULL AFTER amount,
        ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    echo "DONE\n";

    echo "  - Backfilling updated_at from created_at... ";
    $pdo->exec("UPDATE orders SET updated_at = created_at WHERE updated_at IS NULL");
    echo "DONE\n";

    echo "\nMigration 009 complete.\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
