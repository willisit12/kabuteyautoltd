<?php
require 'includes/config.php';
require 'includes/db.php';
$db = getDB();
$stmt = $db->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE'");
echo "Available cars: " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM cars");
echo "Total cars: " . $stmt->fetchColumn() . PHP_EOL;
$stmt = $db->query("SELECT DISTINCT status FROM cars");
echo "Statuses: " . implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN)) . PHP_EOL;
?>
