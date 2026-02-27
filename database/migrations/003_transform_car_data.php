<?php
/**
 * 003_transform_car_data.php
 * Migrates existing JSON features and columns into the new structured EAV system.
 */

return function($pdo) {
    echo "  - Creating spec categories... ";
    $categories = [
        'Mechanical' => 10,
        'Exterior' => 20,
        'Interior' => 30,
        'Safety' => 40,
        'Technology' => 50,
        'General' => 60
    ];

    $catIds = [];
    foreach ($categories as $catName => $order) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO car_spec_categories (name, order_index) VALUES (?, ?)");
        $stmt->execute([$catName, $order]);
        $catIds[$catName] = $pdo->query("SELECT id FROM car_spec_categories WHERE name = '$catName'")->fetchColumn();
    }
    echo "DONE\n";

    echo "  - Migrating features to structured specs... ";
    // Get all cars with features
    $cars = $pdo->query("SELECT id, features, engine_capacity, drive_train, seats, doors FROM cars")->fetchAll(PDO::FETCH_ASSOC);
    
    // Pre-define some common specs to ensure they exist
    $commonSpecs = [
        ['label' => 'Engine Capacity', 'cat' => 'Mechanical', 'unit' => 'cc'],
        ['label' => 'Drive Train', 'cat' => 'Mechanical', 'unit' => null],
        ['label' => 'Seats', 'cat' => 'General', 'unit' => null],
        ['label' => 'Doors', 'cat' => 'General', 'unit' => null],
        ['label' => 'Air Conditioning', 'cat' => 'Interior', 'unit' => null],
        ['label' => 'Sunroof', 'cat' => 'Exterior', 'unit' => null],
        ['label' => 'Bluetooth', 'cat' => 'Technology', 'unit' => null],
        ['label' => 'Reverse Camera', 'cat' => 'Safety', 'unit' => null]
    ];

    $specIds = [];
    foreach ($commonSpecs as $s) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO car_spec_definitions (category_id, label, unit) VALUES (?, ?, ?)");
        $stmt->execute([$catIds[$s['cat']], $s['label'], $s['unit']]);
        $specIds[$s['label']] = $pdo->query("SELECT id FROM car_spec_definitions WHERE label = '{$s['label']}'")->fetchColumn();
    }

    foreach ($cars as $car) {
        // 1. Handle explicit columns first
        $metadata = [
            'Engine Capacity' => $car['engine_capacity'],
            'Drive Train' => $car['drive_train'],
            'Seats' => $car['seats'],
            'Doors' => $car['doors']
        ];

        foreach ($metadata as $label => $val) {
            if ($val !== null && $val !== '') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO car_spec_values (car_id, spec_def_id, value) VALUES (?, ?, ?)");
                $stmt->execute([$car['id'], $specIds[$label], (string)$val]);
            }
        }

        // 2. Handle JSON features
        $features = json_decode($car['features'], true);
        if (is_array($features)) {
            foreach ($features as $feature) {
                // Determine category based on keywords (simple heuristic)
                $cat = 'General';
                $fLower = strtolower($feature);
                if (str_contains($fLower, 'airbag') || str_contains($fLower, 'abs') || str_contains($fLower, 'brake')) $cat = 'Safety';
                else if (str_contains($fLower, 'leather') || str_contains($fLower, 'seat') || str_contains($fLower, 'ac')) $cat = 'Interior';
                else if (str_contains($fLower, 'alloy') || str_contains($fLower, 'sunroof') || str_contains($fLower, 'light')) $cat = 'Exterior';
                else if (str_contains($fLower, 'bluetooth') || str_contains($fLower, 'screen') || str_contains($fLower, 'speaker')) $cat = 'Technology';

                // Ensure spec definition exists
                if (!isset($specIds[$feature])) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO car_spec_definitions (category_id, label) VALUES (?, ?)");
                    $stmt->execute([$catIds[$cat], $feature]);
                    $specIds[$feature] = $pdo->query("SELECT id FROM car_spec_definitions WHERE label = " . $pdo->quote($feature))->fetchColumn();
                }

                // Insert value
                $stmt = $pdo->prepare("INSERT IGNORE INTO car_spec_values (car_id, spec_def_id, value) VALUES (?, ?, ?)");
                $stmt->execute([$car['id'], $specIds[$feature], 'Yes']);
            }
        }
    }
    echo "DONE\n";
};
