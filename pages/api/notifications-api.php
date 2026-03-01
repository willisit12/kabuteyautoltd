<?php
/**
 * pages/api/notifications-api.php
 * Handles frontend interactions for customer notifications
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Only logged in users
if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

$user = getUserInfo();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Invalid method'], 405);
}

// Receive JSON data
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$id = intval($data['id'] ?? 0);

if (!$action || !$id) {
    jsonResponse(['status' => 'error', 'message' => 'Missing action or item id'], 400);
}

if ($action === 'mark_read') {
    if (markNotificationAsRead($id, $user['id'])) {
        jsonResponse(['status' => 'success']);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Failed to mark as read'], 500);
    }
}

if ($action === 'delete') {
    if (deleteNotification($id, $user['id'])) {
        jsonResponse(['status' => 'success']);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Failed to delete notification'], 500);
    }
}

jsonResponse(['status' => 'error', 'message' => 'Unknown action'], 400);
?>
