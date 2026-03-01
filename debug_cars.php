<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

echo "Total cars in DB: " . $db->query("SELECT COUNT(*) FROM cars")->fetchColumn() . "\n";
echo "Cars with status = 'AVAILABLE': " . $db->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE'")->fetchColumn() . "\n";

$statuses = $db->query("SELECT status, COUNT(*) as count FROM cars GROUP BY status")->fetchAll();
echo "\nBreakdown by status:\n";
foreach ($statuses as $s) {
    echo "- {$s['status']}: {$s['count']}\n";
}

$makes = $db->query("SELECT m.name, COUNT(c.id) as count FROM makes m LEFT JOIN cars c ON c.make_id = m.id GROUP BY m.id")->fetchAll();
echo "\nBreakdown by make:\n";
foreach ($makes as $m) {
    echo "- {$m['name']}: {$m['count']}\n";
}

$no_make = $db->query("SELECT COUNT(*) FROM cars WHERE make_id IS NULL OR make_id = 0")->fetchColumn();
echo "\nCars with no make_id: " . $no_make . "\n";

$no_body = $db->query("SELECT COUNT(*) FROM cars WHERE body_type_id IS NULL OR body_type_id = 0")->fetchColumn();
echo "\nCars with no body_type_id: " . $no_body . "\n";

$public_count = searchCars([], 100, 0, true);
echo "\nsearchCars([]) (countOnly=true): " . $public_count . "\n";
?>
