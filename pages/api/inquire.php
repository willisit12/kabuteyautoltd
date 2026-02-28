<?php
/**
 * pages/api/inquire.php - AJAX inquiry submission
 */
require_once __DIR__ . '/../../includes/functions.php';

// Security check
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

$user = getUserInfo();
$data = json_decode(file_get_contents('php://input'), true);
$car_id = intval($data['car_id'] ?? 0);
$subject = clean($data['subject'] ?? 'Generic Inquiry');
$message = clean($data['message'] ?? '');

if (!$car_id || empty($message)) {
    jsonResponse(['error' => 'Inquiry signature invalid. Message required.'], 400);
}

$db = getDB();

try {
    $db->beginTransaction();

    // 1. Create Inquiry
    $stmt = $db->prepare("INSERT INTO inquiries (car_id, user_id, subject, name, email, phone, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $car_id, 
        $user['id'], 
        $subject, 
        $user['name'], 
        $user['email'], 
        $user['phone'], 
        $message
    ]);
    $inquiry_id = $db->lastInsertId();

    // 2. Create first message in thread
    $stmt = $db->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$inquiry_id, $user['id'], $message]);

    // 3. Optional: Notify Admin (Logic can be added here)

    $db->commit();
    
    jsonResponse([
        'status' => 'success', 
        'message' => 'Concierge inquiry dispatched successfully.',
        'inquiry_id' => $inquiry_id
    ]);
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(['error' => 'Dispatch failure: ' . $e->getMessage()], 500);
}
