<?php
/**
 * scripts/migrate.php
 * Custom Migration Runner for Local & cPanel Synchronization
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Security check for web-based execution
if (php_sapi_name() !== 'cli') {
    $secret = $_GET['key'] ?? '';
    if ($secret !== 'elite_migration_2026') { // Simple secret for protection
        die('Unauthorized access.');
    }
}

try {
    $pdo = getDB();
    
    echo "Starting Migration Process...\n";
    
    // 1. Ensure migrations table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // 2. Get executed migrations
    $executed = $pdo->query("SELECT migration_name FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
    
    // 3. Scan for new migrations
    $migrationDir = __DIR__ . '/../database/migrations';
    $files = scandir($migrationDir);
    $newMigrations = [];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (!in_array($ext, ['sql', 'php'])) continue;
        
        if (!in_array($file, $executed)) {
            $newMigrations[] = $file;
        }
    }
    
    sort($newMigrations); // Ensure numerical order (001, 002...)

    if (empty($newMigrations)) {
        echo "Database is already up to date.\n";
        exit;
    }

    foreach ($newMigrations as $migration) {
        echo "Executing: $migration... ";
        
        $filePath = "$migrationDir/$migration";
        $ext = pathinfo($migration, PATHINFO_EXTENSION);
        
        $pdo->beginTransaction();
        
        try {
            if ($ext === 'sql') {
                $sql = file_get_contents($filePath);
                if (!empty(trim($sql))) {
                    $pdo->exec($sql);
                }
            } else if ($ext === 'php') {
                // PHP migrations can handle complex data logic
                // Ensure the PHP file returns a closure or contains executable logic
                $result = include $filePath;
                if (is_callable($result)) {
                    $result($pdo);
                }
            }
            
            // Record execution
            $stmt = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$migration]);
            
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            echo "DONE\n";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "FAILED\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\nAll migrations completed successfully!\n";

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
