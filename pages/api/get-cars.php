<?php
/**
 * pages/api/get-cars.php
 * Endpoint for "Load More" functionality
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$page = intval($_GET['page'] ?? 1);
$limit = 8;
$offset = ($page - 1) * $limit;

$filters = [
    'make_id' => $_GET['make_id'] ?? '',
    'body_type_id' => $_GET['body_type_id'] ?? '',
    'year_from' => $_GET['year_from'] ?? '',
    'year_to' => $_GET['year_to'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'mileage_min' => $_GET['mileage_min'] ?? '',
    'mileage_max' => $_GET['mileage_max'] ?? '',
    'transmission' => $_GET['transmission'] ?? '',
    'fuel_type' => $_GET['fuel_type'] ?? '',
    'seats' => $_GET['seats'] ?? '',
    'drive_train' => $_GET['drive_train'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? ''
];

$countOnly = isset($_GET['count_only']) && $_GET['count_only'] == '1';

if ($countOnly) {
    header('Content-Type: application/json');
    $count = searchCars($filters, 0, 0, true);
    echo json_encode(['total' => $count]);
    exit;
}

require_once __DIR__ . '/../../includes/component/car-card.php';

$cars = searchCars($filters, $limit, $offset);
$favoriteIds = isLoggedIn() ? getUserFavoriteIds($_SESSION['user_id']) : [];

if (empty($cars) && $page === 1) {
    echo '<div class="col-span-full py-12 text-center text-gray-500">' . __('No vehicles found matching your criteria.') . '</div>';
    exit;
}

foreach ($cars as $car):
    $car['is_favorited'] = in_array($car['id'], $favoriteIds);
    renderCarCard($car);
endforeach;
