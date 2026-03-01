<?php
/**
 * pages/api/chat-message.php
 * AJAX endpoint for sending inquiry messages (customer & staff)
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$inquiryId = intval($data['inquiry_id'] ?? 0);
$message   = trim($data['message'] ?? '');

if (!$inquiryId || !$message) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

$user = getUserInfo();
$db   = getDB();

// Security check: Customer must own the inquiry, staff can reply to any
if (!isStaffRole()) {
    $stmt = $db->prepare("SELECT id FROM inquiries WHERE id = ? AND user_id = ?");
    $stmt->execute([$inquiryId, $user['id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
}

$sent = sendInquiryMessage($inquiryId, $user['id'], clean($message));

if (!$sent) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    exit;
}

// Fire notification for the other party
if (isStaffRole()) {
    // Notify customer
    $stmt = $db->prepare("SELECT user_id, subject FROM inquiries WHERE id = ?");
    $stmt->execute([$inquiryId]);
    $inq = $stmt->fetch();
    if ($inq && $inq['user_id']) {
        createNotification(
            $inq['user_id'],
            "New Reply: " . ($inq['subject'] ?: 'Vehicle Inquiry'),
            "The team has responded to your inquiry.",
            'SUCCESS',
            'customer/inquiries?id=' . $inquiryId
        );
    }
} else {
    // Notify Staff when a customer sends a message
    notifyStaff(
        "Inquiry Update: " . ($user['name'] ?: 'Customer'),
        "A customer has replied to an inquiry thread: " . clean($message),
        'INFO',
        'admin/inquiries/chat.php?id=' . $inquiryId
    );
}

// Return the new message HTML snippet
$created_at = date('Y-m-d H:i:s');
echo json_encode([
    'status'  => 'success',
    'message' => [
        'sender_id'  => $user['id'],
        'sender_name'=> $user['name'],
        'role'       => $user['role'],
        'message'    => clean($message),
        'created_at' => $created_at,
    ]
]);
?>
