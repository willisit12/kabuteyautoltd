<?php
/**
 * admin/delete-car.php - Background Action
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();

$id = intval($_GET['id'] ?? 0);
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

        // Wipe from database
        $db->prepare("DELETE FROM car_images WHERE car_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM cars WHERE id = ?")->execute([$id]);

        setFlash('success', 'Car deleted from collection.');
    } catch (PDOException $e) {
        setFlash('error', 'Deletion failed: ' . $e->getMessage());
    }
}
redirect('../dashboard.php');
?>