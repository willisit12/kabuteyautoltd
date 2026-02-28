<?php
/**
 * pages/api/orders.php - AJAX order creation
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

// Check if car exists and is available
$stmt = $db->prepare("SELECT price, status FROM cars WHERE id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car || $car['status'] !== 'AVAILABLE') {
    jsonResponse(['error' => 'Vehicle is currently unavailable for acquisition'], 403);
}

try {
    $db->beginTransaction();

    // Create Order
    $stmt = $db->prepare("INSERT INTO orders (user_id, car_id, amount, status, payment_method) VALUES (?, ?, ?, 'PENDING', 'Bank Wire / On-Site')");
    $stmt->execute([$user['id'], $car_id, $car['price']]);
    $order_id = $db->lastInsertId();

    // Update Car Status to RESERVED (optional, but professional)
    $stmt = $db->prepare("UPDATE cars SET status = 'RESERVED' WHERE id = ?");
    $stmt->execute([$car_id]);

    $db->commit();
    
    jsonResponse([
        'status' => 'success', 
        'message' => 'Acquisition protocol initiated.', 
        'order_id' => $order_id
    ]);
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(['error' => 'Acquisition failure: ' . $e->getMessage()], 500);
}
