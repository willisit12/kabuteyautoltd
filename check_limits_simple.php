<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=williams_auto;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $res = $pdo->query("SELECT MAX(price) as max_price, MAX(mileage) as max_mileage FROM cars WHERE status = 'AVAILABLE'")->fetch();
    echo "Max Price: " . $res['max_price'] . "\n";
    echo "Max Mileage: " . $res['max_mileage'] . "\n";

    $over50k = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE' AND price > 50000")->fetchColumn();
    echo "Cars over \$50k: " . $over50k . "\n";

    $over200k = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE' AND mileage > 200000")->fetchColumn();
    echo "Cars over 200k mi: " . $over200k . "\n";
    
    $total = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE'")->fetchColumn();
    echo "Total Available: " . $total . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
