<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "=== Final Import Quality Check ===" . PHP_EOL . PHP_EOL;

$stmt = $pdo->query("SELECT COUNT(*) FROM cars WHERE imported_at IS NOT NULL");
echo "Total imported: " . $stmt->fetchColumn() . PHP_EOL . PHP_EOL;

$stmt = $pdo->query("SELECT
    SUM(fuel_type IS NULL) as null_fuel,
    SUM(transmission IS NULL) as null_trans,
    SUM(`condition` IS NULL) as null_cond,
    SUM(body_type_id IS NULL) as null_body,
    SUM(make_id IS NULL) as null_make_id
FROM cars WHERE imported_at IS NOT NULL");
$nulls = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Null field counts:" . PHP_EOL;
foreach ($nulls as $k => $v) {
    $icon = $v == 0 ? '✓' : '⚠';
    echo "  [$icon] $k: $v" . PHP_EOL;
}

echo PHP_EOL . "=== Remaining nulls detail ===" . PHP_EOL;

if ($nulls['null_fuel'] > 0) {
    $stmt = $pdo->query("SELECT id, make, model, engine_capacity FROM cars WHERE imported_at IS NOT NULL AND fuel_type IS NULL");
    echo "Missing fuel_type:" . PHP_EOL;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo "  [{$r['id']}] {$r['make']} {$r['model']} engine={$r['engine_capacity']}" . PHP_EOL;
}

if ($nulls['null_body'] > 0) {
    $stmt = $pdo->query("SELECT id, make, model FROM cars WHERE imported_at IS NOT NULL AND body_type_id IS NULL");
    echo "Missing body_type_id:" . PHP_EOL;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo "  [{$r['id']}] {$r['make']} {$r['model']}" . PHP_EOL;
}

if ($nulls['null_cond'] > 0) {
    $stmt = $pdo->query("SELECT id, make, model, condition_score FROM cars WHERE imported_at IS NOT NULL AND `condition` IS NULL");
    echo "Missing condition:" . PHP_EOL;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo "  [{$r['id']}] {$r['make']} {$r['model']} score={$r['condition_score']}" . PHP_EOL;
}

echo PHP_EOL . "=== Fuel type distribution ===" . PHP_EOL;
$stmt = $pdo->query("SELECT fuel_type, COUNT(*) as cnt FROM cars WHERE imported_at IS NOT NULL GROUP BY fuel_type ORDER BY cnt DESC");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . ($r['fuel_type'] ?? 'NULL') . ": {$r['cnt']}" . PHP_EOL;
}

echo PHP_EOL . "=== Body type distribution ===" . PHP_EOL;
$stmt = $pdo->query("SELECT bt.name, COUNT(*) as cnt FROM cars c LEFT JOIN body_types bt ON c.body_type_id = bt.id WHERE c.imported_at IS NOT NULL GROUP BY bt.name ORDER BY cnt DESC");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . ($r['name'] ?? 'NULL') . ": {$r['cnt']}" . PHP_EOL;
}

echo PHP_EOL . "=== Condition distribution ===" . PHP_EOL;
$stmt = $pdo->query("SELECT `condition`, COUNT(*) as cnt FROM cars WHERE imported_at IS NOT NULL GROUP BY `condition` ORDER BY cnt DESC");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . ($r['condition'] ?? 'NULL') . ": {$r['cnt']}" . PHP_EOL;
}
