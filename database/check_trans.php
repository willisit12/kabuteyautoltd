<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

// All EVs use single-speed (automatic), VW T-ROC has DSG (automatic)
// Map: car_id => transmission
$fixes = [
    53 => 'AUTOMATIC', // AION UT - EV
    56 => 'AUTOMATIC', // Changan Benben E-Star - EV
    57 => 'AUTOMATIC', // Chery Tiggo 8 PRO New Energy - PHEV
    59 => 'AUTOMATIC', // Feifan R7 - EV
    63 => 'AUTOMATIC', // Hengrun HRS1 - EV
    76 => 'AUTOMATIC', // VW ID.UNYX - EV
    78 => 'AUTOMATIC', // VW T-ROC - DSG (dual-clutch automatic)
    80 => 'AUTOMATIC', // Xiaomi YU7 - EV
];

$stmt = $pdo->prepare("UPDATE cars SET transmission = ? WHERE id = ?");
foreach ($fixes as $id => $trans) {
    $stmt->execute([$trans, $id]);
    echo "Fixed car $id → $trans" . PHP_EOL;
}
echo "Done." . PHP_EOL;
