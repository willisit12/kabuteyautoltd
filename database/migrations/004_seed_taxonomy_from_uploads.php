<?php
/**
 * 004_seed_taxonomy_from_uploads.php
 * Migration script to seed makes and body types from the uploads directory.
 * Extracts names from filenames and flags popular makes.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

echo "Starting Taxonomy Seeding from Uploads...\n";

try {
    $db = getDB();
    
    // Paths
    $baseUploads = __DIR__ . '/../../uploads/cars';
    $allMakesPath = $baseUploads . '/all_makes';
    $popularMakesPath = $baseUploads . '/popular_makes';
    $typesPath = $baseUploads . '/type';

    // 1. Ensure columns exist (in case we're running this on an old schema)
    $db->exec("
        CREATE TABLE IF NOT EXISTS `makes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `logo_url` VARCHAR(255) NULL,
            `is_popular` BOOLEAN DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `body_types` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `icon_url` VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Add is_popular flag if missing (for existing makes table)
    try {
        $db->exec("ALTER TABLE makes ADD COLUMN is_popular BOOLEAN DEFAULT 0");
    } catch (PDOException $e) { /* Column might exist */ }

    // Prepare statements
    $insertMake = $db->prepare("INSERT INTO makes (name, logo_url, is_popular) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE logo_url = VALUES(logo_url), is_popular = VALUES(is_popular)");
    $insertType = $db->prepare("INSERT INTO body_types (name, icon_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE icon_url = VALUES(icon_url)");

    // Helper to format name (e.g., "Mercedes-Benz.png" -> "Mercedes-Benz", "Mini_Van.png" -> "Mini Van")
    function formatName($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        return str_replace('_', ' ', $name);
    }

    $makesSeeded = 0;
    $typesSeeded = 0;

    // 2. Process All Makes
    echo "- Scanning all_makes...\n";
    if (is_dir($allMakesPath)) {
        $files = scandir($allMakesPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $name = formatName($file);
            $logoUrl = "/car-website/uploads/cars/all_makes/$file";
            
            // First treat as not popular. We will update popular ones next.
            $insertMake->execute([$name, $logoUrl, 0]);
            $makesSeeded++;
        }
    } else {
        echo "  [WARNING] Directory not found: $allMakesPath\n";
    }

    // 3. Process Popular Makes
    echo "- Scanning popular_makes...\n";
    if (is_dir($popularMakesPath)) {
        $files = scandir($popularMakesPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $name = formatName($file);
            // Some distinct popular URLs might exist or we just flag the existing one.
            // Using the all_makes path standard, but marking as popular
            $logoUrl = "/car-website/uploads/cars/popular_makes/$file"; 
            
            $insertMake->execute([$name, $logoUrl, 1]);
        }
    } else {
        echo "  [WARNING] Directory not found: $popularMakesPath\n";
    }

    // 4. Process Body Types
    echo "- Scanning body_types...\n";
    if (is_dir($typesPath)) {
        $files = scandir($typesPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $name = formatName($file);
            $iconUrl = "/car-website/uploads/cars/type/$file";
            
            $insertType->execute([$name, $iconUrl]);
            $typesSeeded++;
        }
    } else {
        echo "  [WARNING] Directory not found: $typesPath\n";
    }

    echo "DONE.\n";
    echo "- Seeded $makesSeeded Makes.\n";
    echo "- Seeded $typesSeeded Body Types.\n";

} catch (PDOException $e) {
    echo "FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
