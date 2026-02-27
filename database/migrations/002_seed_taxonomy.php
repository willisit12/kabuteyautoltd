<?php
/**
 * 002_seed_taxonomy.php
 * Extracts existing makes and styles from cars table and populates taxonomy tables.
 */

return function($pdo) {
    echo "  - Seeding makes... ";
    // 1. Extract unique makes
    $makes = $pdo->query("SELECT DISTINCT make FROM cars WHERE make IS NOT NULL AND make != ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($makes as $make) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO makes (name) VALUES (?)");
        $stmt->execute([$make]);
    }
    echo "DONE (" . count($makes) . " indexed)\n";

    echo "  - Seeding body types... ";
    // 2. Extract unique body types
    $types = $pdo->query("SELECT DISTINCT body_type FROM cars WHERE body_type IS NOT NULL AND body_type != ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($types as $type) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO body_types (name) VALUES (?)");
        $stmt->execute([$type]);
    }
    echo "DONE (" . count($types) . " indexed)\n";

    echo "  - Linking cars to taxonomy... ";
    // 3. Update car make_id
    $pdo->exec("
        UPDATE cars c
        JOIN makes m ON c.make COLLATE utf8mb4_general_ci = m.name COLLATE utf8mb4_general_ci
        SET c.make_id = m.id
    ");

    // 4. Update car body_type_id
    $pdo->exec("
        UPDATE cars c
        JOIN body_types bt ON c.body_type COLLATE utf8mb4_general_ci = bt.name COLLATE utf8mb4_general_ci
        SET c.body_type_id = bt.id
    ");
    echo "DONE\n";
};
