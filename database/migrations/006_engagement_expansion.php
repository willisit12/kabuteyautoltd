<?php
/**
 * 006_engagement_expansion.php
 * Implements tracking, threaded messaging, and a notification engine.
 */

return function($pdo) {
    echo "  - Enhancing orders table for tracking... ";
    try {
        $pdo->exec("ALTER TABLE orders 
            ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS tracking_details TEXT DEFAULT NULL");
        echo "DONE\n";
    } catch (Exception $e) {
        echo "ALREADY EXISTS or ERROR: " . $e->getMessage() . "\n";
    }

    echo "  - Creating inquiry_messages table for threaded chat... ";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS inquiry_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inquiry_id INT NOT NULL,
            sender_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Migrate legacy message from inquiries table to the thread
        $stmt = $pdo->query("SELECT id, user_id, message, created_at FROM inquiries");
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertStmt = $pdo->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_id, message, created_at) VALUES (?, ?, ?, ?)");
        foreach ($inquiries as $inq) {
            // Check if first message already exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM inquiry_messages WHERE inquiry_id = ?");
            $check->execute([$inq['id']]);
            if ($check->fetchColumn() == 0) {
                // If user_id is null (guest), we can't link sender_id effectively without changing schema, 
                // but for this phase we assume authenticated users for chat.
                // If user_id is null, we'll skip or use a system ID if we had one.
                if ($inq['user_id']) {
                    $insertStmt->execute([$inq['id'], $inq['user_id'], $inq['message'], $inq['created_at']]);
                }
            }
        }
        echo "DONE\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }

    echo "  - Creating notifications table... ";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('INFO', 'SUCCESS', 'WARNING', 'DANGER') DEFAULT 'INFO',
            link VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "DONE\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "  - Ensuring inquiries have subject and user link... ";
    // This was partially in 005, but let's be double sure for consistency
    try {
        $columns = $pdo->query("DESCRIBE inquiries")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('subject', $columns)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN subject VARCHAR(255) DEFAULT NULL AFTER car_id");
        }
        if (!in_array('user_id', $columns)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN user_id INT DEFAULT NULL");
            $pdo->exec("ALTER TABLE inquiries ADD CONSTRAINT fk_inquiry_user_new FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        }
        echo "DONE\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
};
