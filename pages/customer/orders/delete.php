<?php
/**
 * pages/customer/orders/delete.php
 * Logic only: Terminate acquisition request
 */
require_once __DIR__ . '/../../../includes/functions.php';

requireAuth();
$user = getUserInfo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        setFlash('error', 'Security integrity compromised. Request denied.');
        redirect(url('customer/orders'));
    }

    $order_id = $_POST['order_id'] ?? 0;
    
    $db = getDB();
    // Ensure the order belongs to this user before deleting
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'PENDING'");
    $stmt->execute([$order_id, $user['id']]);

    if ($stmt->rowCount() > 0) {
        setFlash('success', 'Acquisition request terminated successfully.');
    } else {
        setFlash('error', 'Unable to terminate request. It may already be processed or the record was not found.');
    }
}

redirect(url('customer/orders'));
