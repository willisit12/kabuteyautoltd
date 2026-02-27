<?php
/**
 * includes/functions.php
 * Global helper functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php'; // Load localization engine
require_once __DIR__ . '/db.php';

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

// Format price with dynamic currency conversion and localized symbols
function formatPrice($price) {
    if (!$price) return __('Contact for Price');
    
    // Convert price to current currency
    $convertedPrice = I18n::convert($price);
    $currencyCode = I18n::getCurrency();
    $locale = I18n::getLocale();

    // Mapping locale to proper ISO format for NumberFormatter
    $intlLocale = ($locale === 'es') ? 'es_ES' : 'en_US';
    
    // Fallback if intl extension (NumberFormatter) is not enabled
    if (!class_exists('NumberFormatter')) {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED '
        ];
        $symbol = $symbols[$currencyCode] ?? $currencyCode . ' ';
        return $symbol . number_format($convertedPrice, 0);
    }
    
    $formatter = new NumberFormatter($intlLocale, NumberFormatter::CURRENCY);
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
?>
