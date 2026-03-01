<?php
/**
 * admin/delete-car.php - Background Action / API Endpoint
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $db = getDB();
        
        // Retrieve and obliterate associated assets
        $stmt = $db->prepare("SELECT url FROM car_images WHERE car_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        foreach ($images as $img) {
            $path = __DIR__ . '/../../' . ltrim($img['url'], '/');
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        // Instead of cascading delete, set car_id to NULL to retain inquiry and order history
        $db->prepare("UPDATE inquiries SET car_id = NULL WHERE car_id = ?")->execute([$id]);
        $db->prepare("UPDATE orders SET car_id = NULL WHERE car_id = ?")->execute([$id]);
        
        // Also check if there's a test_drives table (optional, catching just in case)
        try {
            $db->prepare("UPDATE test_drives SET car_id = NULL WHERE car_id = ?")->execute([$id]);
        } catch (PDOException $e) { /* ignore if table doesn't exist */ }

        // Wipe from database
        $db->prepare("DELETE FROM car_images WHERE car_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM cars WHERE id = ?")->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Car deleted from collection.']);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if ($e->getCode() == 23000 || strpos($msg, '1451') !== false) {
            $msg = 'Cannot delete this vehicle because it is linked to existing orders or records. Please remove those records first.';
        }
        echo json_encode(['status' => 'error', 'message' => $msg]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vehicle ID.']);
}
exit;
?>