<?php
/**
 * database/backfill_imported.php
 * Backfills fuel_type, body_type_id, and condition for already-imported scraped cars.
 *
 * Usage: php database/backfill_imported.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();

// ─── Fuel Type Inference ──────────────────────────────────────────────────────

// Maps car ID → fuel type based on engine, description, make/model keywords
function inferFuelTypeFromRecord(array $r): ?string {
    $haystack = strtolower(implode(' ', array_filter([
        $r['engine_capacity'], $r['description'], $r['make'], $r['model']
    ])));

    // EV / Electric
    $evKeywords = ['electric', ' ev ', 'pure ev', 'bev', 'id.', 'id.unyx', 'feifan', 'aion', 'xiaomi yu7', 'benben e-star', 'monpike e'];
    foreach ($evKeywords as $kw) {
        if (str_contains($haystack, $kw)) return 'ELECTRIC';
    }

    // PHEV / Plugin Hybrid
    $phevKeywords = ['phev', 'plug-in', 'plugin', 'new energy', 'erev', '100km'];
    foreach ($phevKeywords as $kw) {
        if (str_contains($haystack, $kw)) return 'PLUGIN_HYBRID';
    }

    // Hybrid
    if (str_contains($haystack, 'hybrid') || str_contains($haystack, 'hev')) return 'HYBRID';

    // Diesel — explicit keyword or Ford Transit (always diesel in this market)
    if (str_contains($haystack, 'diesel') || str_contains($haystack, 'ford transit')) return 'DIESEL';

    // Gasoline — engine strings like "1.4T", "2.0L", "3.0T", "6.0L"
    if ($r['engine_capacity'] && preg_match('/^\d+\.\d+[tl]$/i', trim($r['engine_capacity']))) return 'GASOLINE';

    // Gasoline — large displacement engines (GMC Savana 6.0L etc.)
    if ($r['engine_capacity'] && preg_match('/^\d+\.\d+L$/i', trim($r['engine_capacity']))) return 'GASOLINE';

    return null;
}

// ─── Body Type Inference ──────────────────────────────────────────────────────

function inferBodyTypeFromRecord(array $r, array $bodyTypeMap): ?int {
    $make  = strtoupper(trim($r['make']));
    $model = strtoupper(trim($r['model']));
    $desc  = strtoupper(trim($r['description']));
    $combined = "$make $model $desc";

    // SUV patterns
    $suvKeywords = [
        'Q2','Q3','Q5','Q7','Q8',                          // Audi
        'X1','X3','X5','X6','X7',                          // BMW
        'GLC','GLE','GLS','GLK',                           // Mercedes
        'CX-30','CX-5','CX5','CX30',                       // Mazda
        'RAV4','CR-V','CRV','PILOT','PASSPORT','HIGHLANDER',// Toyota/Honda
        'TUCSON','SPORTAGE','PALISADE','TELLURIDE',        // Hyundai/Kia
        'TIGUAN','T-ROC','TANYUE','TAYRON','TOUAREG',      // VW
        'KODIAQ','KAROQ',                                  // Skoda
        'FORESTER','OUTBACK',                              // Subaru
        'STELVIO','MACAN','CAYENNE','URUS',                // Alfa/Porsche/Lambo
        'DEFENDER','DISCOVERY','RANGE ROVER',              // Land Rover
        'EPAO','TIGGO','HRS1','T600','TANYUE',             // Chinese SUVs
        'YU7',                                             // Xiaomi
        'COUNTRYMAN',                                      // MINI
    ];
    foreach ($suvKeywords as $kw) {
        if (str_contains($combined, $kw)) return $bodyTypeMap['suv'] ?? null;
    }

    // Van / MPV
    $vanKeywords = ['TRANSIT','SPRINTER','VITO','V-CLASS','SAVANA','CARNIVAL',
                    'ODYSSEY','SIENNA','CARAVELLE','MONPIKE'];
    foreach ($vanKeywords as $kw) {
        if (str_contains($combined, $kw)) return $bodyTypeMap['van'] ?? null;
    }

    // Truck / Pickup
    if (preg_match('/\b(F-?150|RANGER|TACOMA|TUNDRA|HILUX|NAVARA|AMAROK|SILVERADO|SIERRA|RAM)\b/', $combined)) {
        return $bodyTypeMap['truck'] ?? null;
    }

    // Hatchback
    $hatchKeywords = ['GOLF','POLO','FIESTA','FOCUS','SWIFT','YARIS','CLIO','LIANA HATCHBACK',
                      '1 SERIES','M135I','BENBEN','UT '];
    foreach ($hatchKeywords as $kw) {
        if (str_contains($combined, $kw)) return $bodyTypeMap['hatchback'] ?? null;
    }

    // Sedan — sedans by model name
    $sedanKeywords = ['CONTINENTAL','SUPERB','MAGOTAN','CHAIRMAN','B50','508','K3','MX-5',
                      'D60','V6 '];
    foreach ($sedanKeywords as $kw) {
        if (str_contains($combined, $kw)) return $bodyTypeMap['sedan'] ?? null;
    }

    return null;
}

// ─── Condition Inference ──────────────────────────────────────────────────────

function inferConditionFromScore(?string $score): ?string {
    if ($score === null || $score === '') return null;
    $n = (int)$score;
    if ($n > 10) $n = (int)round($n / 10); // normalise 0-100 → 0-10
    if ($n >= 9) return 'EXCELLENT';
    if ($n >= 7) return 'VERY_GOOD';
    if ($n >= 5) return 'GOOD';
    if ($n >= 1) return 'FAIR';
    return null;
}

// ─── Build body type lookup map (name → id, case-insensitive) ─────────────────

$btStmt = $pdo->query("SELECT id, LOWER(name) as name FROM body_types");
$bodyTypeMap = [];
foreach ($btStmt->fetchAll(PDO::FETCH_ASSOC) as $bt) {
    $bodyTypeMap[$bt['name']] = (int)$bt['id'];
}
// Convenience aliases
$bodyTypeMap['suv']       = $bodyTypeMap['suv'] ?? null;
$bodyTypeMap['sedan']     = $bodyTypeMap['sedan'] ?? null;
$bodyTypeMap['hatchback'] = $bodyTypeMap['hatchback'] ?? null;
$bodyTypeMap['van']       = $bodyTypeMap['van'] ?? $bodyTypeMap['mini van'] ?? null;
$bodyTypeMap['truck']     = $bodyTypeMap['truck'] ?? $bodyTypeMap['pick up'] ?? null;

// ─── Fetch all imported cars ──────────────────────────────────────────────────

$cars = $pdo->query("
    SELECT id, make, model, engine_capacity, description, fuel_type, body_type_id, `condition`, condition_score
    FROM cars WHERE imported_at IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);

$fuelFixed  = 0;
$bodyFixed  = 0;
$condFixed  = 0;

foreach ($cars as $car) {
    $updates = [];
    $params  = [];

    // Fix fuel_type
    if (!$car['fuel_type']) {
        $fuel = inferFuelTypeFromRecord($car);
        if ($fuel) {
            $updates[] = "fuel_type = ?";
            $params[]  = $fuel;
            $fuelFixed++;
        }
    }

    // Fix body_type_id
    if (!$car['body_type_id']) {
        $btId = inferBodyTypeFromRecord($car, $bodyTypeMap);
        if ($btId) {
            $updates[] = "body_type_id = ?";
            $params[]  = $btId;
            $bodyFixed++;
        }
    }

    // Fix condition (score exists but condition enum is null)
    if (!$car['condition'] && $car['condition_score'] !== null) {
        $cond = inferConditionFromScore($car['condition_score']);
        if ($cond) {
            $updates[] = "`condition` = ?";
            $params[]  = $cond;
            $condFixed++;
        }
    }

    if (!empty($updates)) {
        $params[] = $car['id'];
        $pdo->prepare("UPDATE cars SET " . implode(', ', $updates) . " WHERE id = ?")
            ->execute($params);
        echo "[FIXED] Car {$car['id']} ({$car['make']} {$car['model']}): " . implode(', ', $updates) . PHP_EOL;
    }
}

echo PHP_EOL;
echo "Backfill complete." . PHP_EOL;
echo "  fuel_type fixed  : $fuelFixed" . PHP_EOL;
echo "  body_type fixed  : $bodyFixed" . PHP_EOL;
echo "  condition fixed  : $condFixed" . PHP_EOL;
