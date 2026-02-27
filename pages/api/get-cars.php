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
    'make' => $_GET['make'] ?? '',
    'model' => $_GET['model'] ?? '',
    'year' => $_GET['year'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? ''
];

require_once __DIR__ . '/../../includes/component/car-card.php';

$cars = searchCars($filters, $limit, $offset);

if (empty($cars)) {
    http_response_code(204); // No Content
    exit;
}

foreach ($cars as $car):
    renderCarCard($car, 'opacity-0 translate-y-10');
endforeach;
