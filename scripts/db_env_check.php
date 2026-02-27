<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$res = $pdo->query("SHOW CREATE TABLE cars")->fetch(PDO::FETCH_ASSOC);
echo "CARS TABLE:\n";
print_r($res);

$res2 = $pdo->query("SHOW CREATE TABLE makes")->fetch(PDO::FETCH_ASSOC);
echo "\nMAKES TABLE:\n";
print_r($res2);

echo "\nSAMPLE CAR FEATURES:\n";
$samples = $pdo->query("SELECT id, features FROM cars LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($samples as $s) {
    echo "ID: " . $s['id'] . " | FEATURES: " . $s['features'] . "\n";
}
