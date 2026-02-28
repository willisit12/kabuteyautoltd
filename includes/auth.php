<?php
/**
 * includes/auth.php
 * Unified authentication and session management
 */

require_once __DIR__ . '/functions.php';

/**
 * Checks if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Enforces authentication and redirects to login if necessary
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(url('login'));
    }
}

/**
 * Retrieves full information for the currently logged-in user (admin or standard)
 */
function getUserInfo() {
    if (!isLoggedIn()) return null;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, email, name, role, avatar_url, last_login, phone, address, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to fetch user info: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifies if the current user has the 'admin' role
 */
function isAdminRole() {
    $user = getUserInfo();
    return $user && $user['role'] === 'admin';
}

/**
 * Destroys session and redirects to home
 */
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    redirect(url());
}

/**
 * Logs in a user by ID, updates last_login, and sets session
 */
function loginUser($userId) {
    if (!$userId) return false;
    
    $_SESSION['user_id'] = $userId;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Failed to update last login: " . $e->getMessage());
        return false;
    }
}
?>
