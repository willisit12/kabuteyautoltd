<?php
/**
 * includes/functions.php
 * Global helper functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php'; // Load localization engine
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/component/chat-concierge.php'; // High-fidelity chat component

// Generate safe URLs with base path
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

// CSRF Token Generation and Validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Converts a shorthand byte value from php.ini to bytes.
 *
 * @param string $val The shorthand byte value (e.g., "2M", "2048K", "1G").
 * @return int The value in bytes.
 */
function convertShorthandToBytes(string $val): int {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

/**
 * Get the PHP post_max_size in bytes.
 * @return int
 */
function getPostMaxSize(): int {
    return convertShorthandToBytes(ini_get('post_max_size'));
}

/**
 * Check if the POST size exceeds server limits
 */
function isPostSizeExceeded(): bool {
    $maxSize = getPostMaxSize();
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
    
    // Log for debugging
    file_put_contents('size_debug.log', "Content-Length: $contentLength, Max-Size: $maxSize\n", FILE_APPEND);
    
    if ($contentLength > $maxSize) {
        return true;
    }
    return false;
}

// Input Sanitization
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Retrieves the global markup value in CNY from the database
 */
function getGlobalMarkup() {
    static $markup = null;
    if ($markup !== null) return $markup;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT `value` FROM global_settings WHERE `key` = 'markup_cny'");
        $stmt->execute();
        $markup = (float)($stmt->fetchColumn() ?: 0);
    } catch (Exception $e) {
        $markup = 0;
    }
    return $markup;
}

// Format price with dynamic currency conversion, localized symbols, and global markup
function formatPrice($price, $priceUnit = 'USD', $addMarkup = true) {
    if (!$price) return __('Contact for Price');

    // Use priceUnit as source currency (defaulting to USD if empty)
    $fromCurrency = !empty($priceUnit) ? strtoupper(trim($priceUnit)) : 'USD';
    
    // Add global markup if requested
    $totalPrice = (float)$price;
    if ($addMarkup) {
        $markupCny = getGlobalMarkup();
        if ($markupCny > 0) {
            // Convert markup from CNY to the car's price unit
            $markupInCarUnit = I18n::convertBetween($markupCny, 'CNY', $fromCurrency);
            $totalPrice += $markupInCarUnit;
        }
    }

    // Convert to global I18n currency
    $convertedPrice = I18n::convert($totalPrice, $fromCurrency);
    $currencyCode = I18n::getCurrency();
    $locale = I18n::getLocale();
    $intlLocale = ($locale === 'es') ? 'es_ES' : 'en_US';

    if (!class_exists('NumberFormatter')) {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'CNY' => '¥',
            'JPY' => '¥',
            'GHS' => 'GH₵'
        ];
        $symbol = $symbols[$currencyCode] ?? $currencyCode . ' ';
        return $symbol . number_format($convertedPrice, 0);
    }

    $formatter = new NumberFormatter($intlLocale, NumberFormatter::CURRENCY);
    // Remove decimal points for a cleaner "Elite" aesthetic
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
    return $formatter->formatCurrency($convertedPrice, $currencyCode);
}

// Format mileage with localized units
function formatMileage($mileage) {
    if (!$mileage) return '0 mi';
    
    $locale = I18n::getLocale();
    if ($locale === 'es') {
        // Simple conversion for demonstration: 1 mi = 1.60934 km
        $km = round($mileage * 1.60934);
        return number_format($km) . ' km';
    }
    
    return number_format($mileage) . ' mi';
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Redirect helper
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Flash message helpers
function setFlash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * CAR LOGIC
 */

// Get car makes for filters
function getCarMakes() {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, logo_url FROM makes ORDER BY name");
    return $stmt->fetchAll();
}

// Get body types for filters
function getBodyTypes() {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, icon_url FROM body_types ORDER BY name");
    return $stmt->fetchAll();
}

// Search and filter cars
function searchCars($filters = [], $limit = 12, $offset = 0, $countOnly = false) {
    $db = getDB();
    
    if ($countOnly) {
        $sql = "SELECT COUNT(*) FROM cars c WHERE c.status = 'AVAILABLE'";
    } else {
        $sql = "SELECT c.*,
                m.name as make_name,
                bt.name as body_type_name,
                (SELECT url FROM car_images WHERE car_id = c.id ORDER BY `order` ASC LIMIT 1) as primary_image,
                (SELECT COUNT(*) FROM car_images WHERE car_id = c.id) as image_count
                FROM cars c
                LEFT JOIN makes m ON c.make_id = m.id
                LEFT JOIN body_types bt ON c.body_type_id = bt.id
                WHERE c.status = 'AVAILABLE'";
    }
    
    $params = [];
    
    // Normal Filters
    if (!empty($filters['make_id'])) { $sql .= " AND c.make_id = ?"; $params[] = $filters['make_id']; }
    if (!empty($filters['body_type_id'])) { $sql .= " AND c.body_type_id = ?"; $params[] = $filters['body_type_id']; }
    if (!empty($filters['year_from'])) { $sql .= " AND c.year >= ?"; $params[] = $filters['year_from']; }
    if (!empty($filters['year_to'])) { $sql .= " AND c.year <= ?"; $params[] = $filters['year_to']; }
    if (!empty($filters['price_min'])) { $sql .= " AND c.price >= ?"; $params[] = $filters['price_min']; }
    if (!empty($filters['price_max'])) { $sql .= " AND c.price <= ?"; $params[] = $filters['price_max']; }
    if (!empty($filters['mileage_min'])) { $sql .= " AND c.mileage >= ?"; $params[] = $filters['mileage_min']; }
    if (!empty($filters['mileage_max'])) { $sql .= " AND c.mileage <= ?"; $params[] = $filters['mileage_max']; }
    
    if (!empty($filters['transmission'])) { $sql .= " AND c.transmission = ?"; $params[] = $filters['transmission']; }
    if (!empty($filters['fuel_type'])) { $sql .= " AND c.fuel_type = ?"; $params[] = $filters['fuel_type']; }
    if (!empty($filters['seats'])) { $sql .= " AND c.seats = ?"; $params[] = $filters['seats']; }
    if (!empty($filters['drive_train'])) { $sql .= " AND c.drive_train = ?"; $params[] = $filters['drive_train']; }
    
    // Legacy support for make (string) if needed
    if (!empty($filters['make']) && empty($filters['make_id'])) { $sql .= " AND c.make = ?"; $params[] = $filters['make']; }

    // Global search term (model, make)
    if (!empty($filters['search'])) {
        $sql .= " AND (c.model LIKE ? OR c.make LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($countOnly) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // Sorting Logic
    $orderBy = "c.featured DESC, c.created_at DESC"; // Default
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':
                $orderBy = "c.price ASC";
                break;
            case 'price_desc':
                $orderBy = "c.price DESC";
                break;
            case 'newest':
                $orderBy = "c.created_at DESC";
                break;
        }
    }

    $sql .= " ORDER BY {$orderBy} LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get featured cars
function getFeaturedCars($limit = 3) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*,
        (SELECT url FROM car_images WHERE car_id = c.id ORDER BY `order` ASC LIMIT 1) as primary_image
        FROM cars c
        WHERE c.featured = 1 AND c.status = 'AVAILABLE'
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Get recommended cars
function getRecommendedCars($excludeId, $limit = 3) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*,
        (SELECT url FROM car_images WHERE car_id = c.id ORDER BY `order` ASC LIMIT 1) as primary_image
        FROM cars c
        WHERE c.id != ? AND c.status = 'AVAILABLE'
        ORDER BY c.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$excludeId, $limit]);
    return $stmt->fetchAll();
}

// Get single car with all images
function getCarById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
    
    if (!$car) return null;
    
    $stmt = $db->prepare("SELECT * FROM car_images WHERE car_id = ? ORDER BY `order` ASC");
    $stmt->execute([$id]);
    $car['images'] = $stmt->fetchAll();
    
    $stmt = $db->prepare("UPDATE cars SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    return $car;
}
// Get single car with all images by slug
function getCarBySlug($slug) {
    if (empty($slug)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cars WHERE slug = ?");
    $stmt->execute([$slug]);
    $car = $stmt->fetch();
    
    if (!$car) return null;
    
    $stmt = $db->prepare("SELECT * FROM car_images WHERE car_id = ? ORDER BY `order` ASC");
    $stmt->execute([$car['id']]);
    $car['images'] = $stmt->fetchAll();
    
    $stmt = $db->prepare("UPDATE cars SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$car['id']]);
    
    return $car;
}
// Get all favorite car IDs for a user
function getUserFavoriteIds($userId) {
    if (!$userId) return [];
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT car_id FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * NOTIFICATION ENGINE
 */

/**
 * Creates a persistent notification for a user
 */
function createNotification($userId, $title, $message, $type = 'INFO', $link = null) {
    if (!$userId) return false;
    $db = getDB();
    try {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $type, $link]);
    } catch (PDOException $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Broadcasts a notification to all staff (admin/user)
 */
function notifyStaff($title, $message, $type = 'INFO', $link = null) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'user')");
        $stmt->execute();
        $staffMembers = $stmt->fetchAll();
        
        foreach ($staffMembers as $staff) {
            createNotification($staff['id'], $title, $message, $type, $link);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Failed to broadcast staff notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves unread notification count for a user
 */
function getUnreadNotificationsCount($userId) {
    if (!$userId) return 0;
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Retrieves recent notifications for a user
 */
function getRecentNotifications($userId, $limit = 5) {
    if (!$userId) return [];
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, (int)$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Marks a specific notification as read for a user
 */
function markNotificationAsRead($id, $userId) {
    if (!$id || !$userId) return false;
    $db = getDB();
    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    } catch (PDOException $e) {
        error_log("Failed to mark notification $id as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a specific notification for a user
 */
function deleteNotification($id, $userId) {
    if (!$id || !$userId) return false;
    $db = getDB();
    try {
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    } catch (PDOException $e) {
        error_log("Failed to delete notification $id: " . $e->getMessage());
        return false;
    }
}

/**
 * INQUIRY & CHAT HELPERS
 */

/**
 * Retrieves chat messages for a specific inquiry
 */
function getInquiryMessages($inquiryId) {
    $db = getDB();
    try {
        $stmt = $db->prepare("
            SELECT m.*, u.name as sender_name, u.avatar_url, u.role as sender_role
            FROM inquiry_messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE m.inquiry_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([($inquiryId)]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Sends a message in an inquiry thread
 */
function sendInquiryMessage($inquiryId, $senderId, $message) {
    $db = getDB();
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO inquiry_messages (inquiry_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$inquiryId, $senderId, $message]);

        // Update inquiry status if needed
        $user = getUserInfo(); // Uses session, assumes we are the ones sending
        $status = ($user['role'] === 'admin') ? 'REPLIED' : 'PENDING';
        
        $stmt = $db->prepare("UPDATE inquiries SET status = ?, replied_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $inquiryId]);

        $db->commit();
        return true;
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        return false;
    }
}
?>
