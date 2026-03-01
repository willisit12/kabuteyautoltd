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

    $order_id = intval($_POST['order_id'] ?? 0);

    if (!$order_id) {
        setFlash('error', 'Invalid order reference.');
        redirect(url('customer/orders'));
    }

    $db = getDB();

    // Get order details first
    $stmt = $db->prepare("SELECT id, car_id, status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user['id']]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('error', 'Order not found or access denied.');
        redirect(url('customer/orders'));
    }

    // Only allow deletion of PENDING orders
    if ($order['status'] !== 'PENDING') {
        setFlash('error', 'Only pending orders can be deleted. Contact support for assistance.');
        redirect(url('customer/orders'));
    }

    try {
        $db->beginTransaction();

        // Delete the order
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);

        // Release the car back to AVAILABLE
        $stmt = $db->prepare("UPDATE cars SET status = 'AVAILABLE' WHERE id = ?");
        $stmt->execute([$order['car_id']]);

        $db->commit();

        setFlash('success', 'Acquisition request terminated successfully. Vehicle returned to inventory.');
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('error', 'Failed to delete order: ' . $e->getMessage());
    }
}

redirect(url('customer/orders'));
