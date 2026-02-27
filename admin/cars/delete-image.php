<?php
/**
 * admin/delete-image.php - Background Action
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();

$imageId = intval($_GET['id'] ?? 0);
$carId = intval($_GET['car_id'] ?? 0);

if ($imageId > 0 && $carId > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT url FROM car_images WHERE id = ? AND car_id = ?");
        $stmt->execute([$imageId, $carId]);
        $img = $stmt->fetch();

        if ($img) {
            $path = __DIR__ . '/../../' . ltrim($img['url'], '/');
            if (file_exists($path)) {
                @unlink($path);
            }

            $stmt = $db->prepare("DELETE FROM car_images WHERE id = ?");
            $stmt->execute([$imageId]);

            setFlash('success', 'Asset removed from collection.');
        } else {
            setFlash('error', 'Asset signature not found.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Removal failure: ' . $e->getMessage());
    }
}
redirect('edit.php?id=' . $carId);
?>