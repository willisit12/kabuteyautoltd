<?php
/**
 * admin/set-primary.php - Background Action
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();

$imageId = intval($_GET['id'] ?? 0);
$carId = intval($_GET['car_id'] ?? 0);

if ($imageId > 0 && $carId > 0) {
    try {
        $db = getDB();
        // Shift existing order to prioritize the selected asset
        $db->beginTransaction();
        
        // Push everything else back
        $db->prepare("UPDATE car_images SET `order` = `order` + 1 WHERE car_id = ?")->execute([$carId]);
        
        // Set selected to pole position
        $db->prepare("UPDATE car_images SET `order` = 0 WHERE id = ? AND car_id = ?")->execute([$imageId, $carId]);
        
        $db->commit();
        setFlash('success', 'Visual hierarchy synchronized.');
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('error', 'Synchronization failure: ' . $e->getMessage());
    }
}
redirect('edit.php?id=' . $carId);
?>