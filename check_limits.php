<?php
require_once __DIR__ . '/includes/config.php';
$db = getDB();

$res = $db->query("SELECT MAX(price) as max_price, MAX(mileage) as max_mileage FROM cars WHERE status = 'AVAILABLE'")->fetch();
echo "Max Price: " . $res['max_price'] . "\n";
echo "Max Mileage: " . $res['max_mileage'] . "\n";

$over50k = $db->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE' AND price > 50000")->fetchColumn();
echo "Cars over \$50k: " . $over50k . "\n";

$over200k = $db->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE' AND mileage > 200000")->fetchColumn();
echo "Cars over 200k mi: " . $over200k . "\n";
?>
