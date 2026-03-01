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
$stmt = $db->prepare("SELECT price, price_unit, status FROM cars WHERE id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car || $car['status'] !== 'AVAILABLE') {
    jsonResponse(['error' => 'Vehicle is currently unavailable for acquisition'], 403);
}

// Check if user already has a pending order for this car
$stmt = $db->prepare("SELECT id FROM orders WHERE user_id = ? AND car_id = ? AND status = 'PENDING'");
$stmt->execute([$user['id'], $car_id]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'You already have a pending order for this vehicle'], 400);
}

try {
    $db->beginTransaction();

    // Create Order
    $stmt = $db->prepare("INSERT INTO orders (user_id, car_id, amount, price_unit, status, payment_method, created_at) VALUES (?, ?, ?, ?, 'PENDING', 'Bank Wire / On-Site', NOW())");
    $stmt->execute([$user['id'], $car_id, $car['price'], $car['price_unit']]);
    $order_id = $db->lastInsertId();

    // Update Car Status to RESERVED
    $stmt = $db->prepare("UPDATE cars SET status = 'RESERVED' WHERE id = ?");
    $stmt->execute([$car_id]);

    // Create notification for user
    createNotification(
        $user['id'],
        "Order Created: #ORD-" . str_pad((string)$order_id, 5, '0', STR_PAD_LEFT),
        "Your acquisition request has been submitted successfully. Our team will review and contact you shortly.",
        'SUCCESS',
        'customer/orders/view/' . $order_id
    );

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
