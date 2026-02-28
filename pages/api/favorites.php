<?php
/**
 * pages/api/favorites.php - AJAX favorite toggle
 */
require_once __DIR__ . '/../../includes/functions.php';

// Security check
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

$user = getUserInfo();
$data = json_decode(file_get_contents('php://input'), true);
$car_id = intval($data['car_id'] ?? 0);

if (!$car_id) {
    jsonResponse(['error' => 'Vehicle identifier signature invalid'], 400);
}

$db = getDB();

// Toggle Logic
$stmt = $db->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND car_id = ?");
$stmt->execute([$user['id'], $car_id]);
$exists = $stmt->fetch();

try {
    if ($exists) {
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
        $stmt->execute([$user['id'], $car_id]);
        $status = 'removed';
    } else {
        $stmt = $db->prepare("INSERT INTO favorites (user_id, car_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $car_id]);
        $status = 'added';
    }
    
    jsonResponse(['status' => 'success', 'favorite_status' => $status]);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database operation failure: ' . $e->getMessage()], 500);
}
