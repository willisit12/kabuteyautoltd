<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
$db = getDB();

$output = "";
function dumpTable($db, $table, &$output) {
    $output .= "--- TABLE: $table ---\n";
    try {
        $stmt = $db->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $output .= $row[1] . ";\n\n";
    } catch (Exception $e) {
        $output .= "Error: " . $e->getMessage() . "\n\n";
    }
}

dumpTable($db, 'users', $output);
dumpTable($db, 'cars', $output);
dumpTable($db, 'orders', $output);
dumpTable($db, 'inquiries', $output);
dumpTable($db, 'favorites', $output);

file_put_contents('schema_dump.txt', $output);
echo "Dumped to schema_dump.txt\n";
