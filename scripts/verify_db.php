<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();

echo "MIGRATION VERIFICATION REPORT\n";
echo "=============================\n";

$tables = [
    'makes' => "SELECT count(*) FROM makes",
    'body_types' => "SELECT count(*) FROM body_types",
    'car_spec_categories' => "SELECT count(*) FROM car_spec_categories",
    'car_spec_definitions' => "SELECT count(*) FROM car_spec_definitions",
    'car_spec_values' => "SELECT count(*) FROM car_spec_values",
    'cars (linked to makes)' => "SELECT count(*) FROM cars WHERE make_id IS NOT NULL"
];

foreach ($tables as $name => $query) {
    $count = $pdo->query($query)->fetchColumn();
    echo str_pad($name, 25) . ": $count\n";
}

echo "\nSAMPLE SPECS (First Car):\n";
$firstCar = $pdo->query("SELECT id FROM cars LIMIT 1")->fetchColumn();
if ($firstCar) {
    $specs = $pdo->query("
        SELECT d.label, v.value, c.name as category
        FROM car_spec_values v
        JOIN car_spec_definitions d ON v.spec_def_id = d.id
        JOIN car_spec_categories c ON d.category_id = c.id
        WHERE v.car_id = $firstCar
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($specs as $s) {
        echo "[{$s['category']}] {$s['label']}: {$s['value']}\n";
    }
}
