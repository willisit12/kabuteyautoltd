<?php
/**
 * admin/users/delete.php - Wipe User
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAuth();

if (!isAdminRole()) {
    setFlash('error', 'Only Elite Administrators can eradicate identities.');
    redirect('../dashboard.php');
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0 && $id != $_SESSION['user_id']) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Identity successfully purged from system.');
    } catch (PDOException $e) {
        setFlash('error', 'Eradication failure: ' . $e->getMessage());
    }
} else if ($id == $_SESSION['user_id']) {
    setFlash('error', 'Cannot purge own active session identity.');
}

redirect('index.php');
?>
