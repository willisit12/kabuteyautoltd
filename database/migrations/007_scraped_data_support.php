<?php
/**
 * 007_scraped_data_support.php
 * Adds columns to the cars table to support bulk-imported scraped data.
 *
 * Usage: php database/migrations/007_scraped_data_support.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

echo "Running migration 007: Scraped data support...\n";

try {
    $pdo = getDB();

    echo "  - Adding scraped data columns to cars table... ";
    $pdo->exec("ALTER TABLE cars
        ADD COLUMN IF NOT EXISTS condition_score TINYINT UNSIGNED DEFAULT NULL COMMENT '1-10 score from source',
        ADD COLUMN IF NOT EXISTS emission VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS finance_info VARCHAR(500) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS source_url VARCHAR(500) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS price_unit VARCHAR(10) DEFAULT NULL COMMENT 'e.g. CNY, USD',
        ADD COLUMN IF NOT EXISTS imported_at TIMESTAMP NULL DEFAULT NULL");
    echo "DONE\n";

    echo "  - Adding index on source_url for deduplication... ";
    try {
        $pdo->exec("CREATE INDEX idx_cars_source_url ON cars(source_url(255))");
        echo "DONE\n";
    } catch (Exception $e) {
        echo "ALREADY EXISTS\n";
    }

    echo "\nMigration 007 complete.\n";

} catch (Exception $e) {
    echo "FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
