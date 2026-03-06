<?php
/**
 * database/migrations/010_global_markup_settings.php
 * Creates a table for global settings (e.g., markup)
 */

return function(PDO $db) {
    // Create global_settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS global_settings (
            `key` VARCHAR(50) PRIMARY KEY,
            `value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Initialize markup_cny if it doesn't exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM global_settings WHERE `key` = 'markup_cny'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO global_settings (`key`, `value`) VALUES ('markup_cny', '0')");
    }
};
