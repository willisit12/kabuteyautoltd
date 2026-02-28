<?php
/**
 * 005_dashboard_support.php
 * Enhances the schema for customer dashboard features.
 */

return function($pdo) {
    echo "  - Enhancing inquiries table for user linkage... ";
    
    // Check if user_id exists
    $columns = $pdo->query("DESCRIBE inquiries")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('user_id', $columns)) {
        $pdo->exec("ALTER TABLE inquiries ADD COLUMN user_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE inquiries ADD CONSTRAINT fk_inquiry_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
    }

    if (!in_array('subject', $columns)) {
        $pdo->exec("ALTER TABLE inquiries ADD COLUMN subject VARCHAR(255) DEFAULT NULL AFTER car_id");
    }
    
    echo "DONE\n";

    echo "  - Ensuring favorites and orders tables are optimized... ";
    // Add indexes manually since CREATE INDEX IF NOT EXISTS is not standard in all MySQL versions
    try {
        $pdo->exec("CREATE INDEX idx_favorites_user ON favorites(user_id)");
    } catch (Exception $e) {} // Ignore if exists
    
    try {
        $pdo->exec("CREATE INDEX idx_orders_user ON orders(user_id)");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("CREATE INDEX idx_inquiries_user ON inquiries(user_id)");
    } catch (Exception $e) {}
    
    echo "DONE\n";
};
