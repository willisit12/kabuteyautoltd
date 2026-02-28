<?php
/**
 * database/import_scraped.php
 * Bulk imports scraped car JSON files into the database.
 *
 * Usage: php database/import_scraped.php /path/to/scraped/data/
 *
 * Expected directory structure per car:
 *   <car-dir>/data.json
 *   <car-dir>/images/01.jpg, 02.jpg, ...
 */

// Bootstrap — suppress session_start() since we're CLI
define('RUNNING_CLI', true);
if (session_status() === PHP_SESSION_NONE) {
    // Prevent config.php from starting a session in CLI context
}
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// ─── Configuration ────────────────────────────────────────────────────────────

$dataDir      = $argv[1] ?? null;
// Match add.php: images go into uploads/cars/
$uploadCarsDir = rtrim(UPLOAD_DIR, '/') . '/cars/';
$uploadCarsRel = 'uploads/cars/'; // relative path stored in DB, matching add.php line 72

if (!$dataDir || !is_dir($dataDir)) {
    echo "Usage: php import_scraped.php /path/to/scraped/data/\n";
    echo "  Each subdirectory must contain data.json and an images/ folder.\n";
    exit(1);
}

// Ensure uploads/cars/ directory exists
if (!is_dir($uploadCarsDir)) {
    mkdir($uploadCarsDir, 0777, true);
}

// ─── Value Mapping Functions ──────────────────────────────────────────────────

function mapConditionScore(?string $score): ?string {
    if ($score === null || $score === '') return null;
    $n = (int)$score;
    if ($n >= 9) return 'EXCELLENT';
    if ($n >= 7) return 'VERY_GOOD';
    if ($n >= 5) return 'GOOD';
    if ($n >= 1) return 'FAIR';
    return null;
}

function mapTransmission(?string $val): ?string {
    if ($val === null || $val === '') return null;
    $v = strtolower(trim($val));
    if (str_contains($v, 'cvt'))                 return 'CVT';
    if (str_contains($v, 'auto') || $v === 'at') return 'AUTOMATIC';
    if (str_contains($v, 'manual') || $v === 'mt') return 'MANUAL';
    return null;
}

function mapFuelType(?string $val): ?string {
    if ($val === null || $val === '') return null;
    $v = strtolower(trim($val));
    if (str_contains($v, 'electric') || str_contains($v, 'ev'))    return 'ELECTRIC';
    if (str_contains($v, 'plugin') || str_contains($v, 'plug-in')) return 'PLUGIN_HYBRID';
    if (str_contains($v, 'hybrid'))                                 return 'HYBRID';
    if (str_contains($v, 'diesel'))                                 return 'DIESEL';
    if (str_contains($v, 'gasoline') || str_contains($v, 'petrol') || str_contains($v, 'gas')) return 'GASOLINE';
    return null;
}

function inferBodyType(?string $model): ?string {
    if ($model === null) return null;
    $m = strtoupper(trim($model));

    $suvKeywords = ['Q2','Q3','Q5','Q7','Q8','X1','X3','X5','X6','X7','GLC','GLE','GLS',
                    'CX-5','CX-30','CX5','CX30','RAV4','CR-V','CRV','TUCSON','SPORTAGE',
                    'TIGUAN','KODIAQ','KAROQ','FORESTER','OUTBACK','PILOT','PASSPORT',
                    'HIGHLANDER','EXPLORER','ESCAPE','EDGE','EQUINOX','TRAVERSE','PALISADE',
                    'TELLURIDE','SORENTO','SANTA FE','SANTAFE','TUCSON','STELVIO','MACAN',
                    'CAYENNE','URUS','DEFENDER','DISCOVERY','RANGE ROVER','RANGEOVER'];
    foreach ($suvKeywords as $kw) {
        if (str_contains($m, $kw)) return 'SUV';
    }

    if (preg_match('/\b(F-?150|RANGER|TACOMA|TUNDRA|HILUX|NAVARA|AMAROK|SILVERADO|SIERRA|RAM\s*1500)\b/', $m)) {
        return 'TRUCK';
    }

    if (preg_match('/\b(GOLF|POLO|FIESTA|FOCUS|COROLLA\s*HATCH|SWIFT|YARIS|CLIO|208|308|MEGANE|ASTRA)\b/', $m)) {
        return 'HATCHBACK';
    }

    if (preg_match('/\b(TRANSIT|SPRINTER|VITO|CARNIVAL|ODYSSEY|SIENNA|TOWN\s*AND\s*COUNTRY|CARAVELLE)\b/', $m)) {
        return 'VAN';
    }

    return null;
}

// Matches add.php slug pattern: make-model-year_{timestamp}
function generateSlug(string $make, string $model, string $year): string {
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', "$make-$model-$year")));
    $base = trim($base, '-');
    return $base . '_' . time();
}

function ensureUniqueSlug(PDO $pdo, string $slug): string {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE slug = ?");
    $stmt->execute([$slug]);
    while ((int)$stmt->fetchColumn() > 0) {
        $slug .= '_' . rand(100, 999);
        $stmt->execute([$slug]);
    }
    return $slug;
}

// ─── DB Helpers ───────────────────────────────────────────────────────────────

function getOrCreateMake(PDO $pdo, string $makeName): int {
    $stmt = $pdo->prepare("SELECT id FROM makes WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$makeName]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];

    $stmt = $pdo->prepare("INSERT INTO makes (name) VALUES (?)");
    $stmt->execute([$makeName]);
    return (int)$pdo->lastInsertId();
}

function getBodyTypeId(PDO $pdo, ?string $bodyTypeName): ?int {
    if ($bodyTypeName === null) return null;
    $stmt = $pdo->prepare("SELECT id FROM body_types WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$bodyTypeName]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

// Returns relative DB path like "uploads/cars/filename.jpg" matching add.php line 72
function importImage(string $srcPath, int $carId, int $orderIndex, string $uploadCarsDir, string $uploadCarsRel): ?string {
    if (!file_exists($srcPath)) return null;

    $ext     = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) return null;

    // Filename: {timestamp}_{index}_{original} — matches add.php pattern
    $filename = time() . '_' . $carId . '_' . $orderIndex . '_' . basename($srcPath);
    $destPath = rtrim($uploadCarsDir, '/') . '/' . $filename;

    if (!copy($srcPath, $destPath)) return null;

    return rtrim($uploadCarsRel, '/') . '/' . $filename;
}

// ─── Main Import Loop ─────────────────────────────────────────────────────────

$pdo  = getDB();
$dirs = array_filter(glob(rtrim($dataDir, '/') . '/*'), 'is_dir');
sort($dirs);

$total   = count($dirs);
$success = 0;
$skipped = 0;
$errors  = [];

echo "Found $total car directories to process.\n\n";

foreach ($dirs as $carDir) {
    $dirName  = basename($carDir);
    $jsonFile = $carDir . '/data.json';

    if (!file_exists($jsonFile)) {
        echo "[SKIP]  $dirName — no data.json\n";
        $skipped++;
        continue;
    }

    $raw = json_decode(file_get_contents($jsonFile), true);
    if (!$raw) {
        echo "[SKIP]  $dirName — invalid JSON\n";
        $skipped++;
        continue;
    }

    // Deduplication by source_url
    $sourceUrl = $raw['metadata']['source_url'] ?? null;
    if ($sourceUrl) {
        $check = $pdo->prepare("SELECT id FROM cars WHERE source_url = ?");
        $check->execute([$sourceUrl]);
        if ($check->fetchColumn()) {
            echo "[SKIP]  $dirName — already imported\n";
            $skipped++;
            continue;
        }
    }

    // Field extraction
    $make        = trim($raw['make'] ?? '');
    $model       = trim($raw['model'] ?? '');
    $year        = (int)($raw['year'] ?? 0);
    $price       = (float)($raw['price'] ?? 0);
    $priceUnit   = trim($raw['price_unit'] ?? 'CNY');
    $mileage     = (int)($raw['mileage'] ?? 0);
    $vin         = ($raw['vin'] ?? null) ?: null;
    $color       = ($raw['color'] ?? null) ?: null;
    $location    = ($raw['location'] ?? null) ?: null;
    $description = ($raw['description'] ?? null) ?: null;
    $features    = $raw['features'] ?? [];
    $engine      = ($raw['engine'] ?? null) ?: null;
    $driveTrain  = ($raw['drive_mode'] ?? null) ?: null;
    $emission    = ($raw['emission'] ?? null) ?: null;
    $condScore   = ($raw['condition_score'] ?? null) !== null ? (string)$raw['condition_score'] : null;
    $financeInfo = ($raw['finance_info'] ?? null) ?: null;
    $scrapedAt   = $raw['metadata']['scraped_at'] ?? null;

    // Value transformations
    $conditionEnum    = mapConditionScore($condScore);
    $transmissionEnum = mapTransmission($raw['transmission'] ?? null);
    $fuelTypeEnum     = mapFuelType($raw['fuel_type'] ?? null);
    $bodyTypeRaw      = ($raw['body_type'] ?? null) ?: null;
    $bodyTypeName     = $bodyTypeRaw ?: inferBodyType($model);

    // Validate minimum required fields
    if (!$make || !$model || !$year) {
        $errors[] = "$dirName — missing make/model/year";
        echo "[ERROR] $dirName — missing make/model/year\n";
        continue;
    }

    try {
        $pdo->beginTransaction();

        $makeId     = getOrCreateMake($pdo, $make);
        $bodyTypeId = getBodyTypeId($pdo, $bodyTypeName);
        $slug       = ensureUniqueSlug($pdo, generateSlug($make, $model, (string)$year));

        $stmt = $pdo->prepare("
            INSERT INTO cars (
                slug, make, model, year, price, mileage, vin, color,
                fuel_type, transmission, `condition`, description,
                features, location, status,
                make_id, body_type_id, engine_capacity, drive_train,
                condition_score, emission, finance_info,
                source_url, price_unit, imported_at,
                created_at, updated_at
            ) VALUES (
                :slug, :make, :model, :year, :price, :mileage, :vin, :color,
                :fuel_type, :transmission, :condition, :description,
                :features, :location, 'AVAILABLE',
                :make_id, :body_type_id, :engine_capacity, :drive_train,
                :condition_score, :emission, :finance_info,
                :source_url, :price_unit, :imported_at,
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':slug'            => $slug,
            ':make'            => $make,
            ':model'           => $model,
            ':year'            => $year,
            ':price'           => $price,
            ':mileage'         => $mileage,
            ':vin'             => $vin,
            ':color'           => $color,
            ':fuel_type'       => $fuelTypeEnum,
            ':transmission'    => $transmissionEnum,
            ':condition'       => $conditionEnum,
            ':description'     => $description,
            ':features'        => json_encode($features, JSON_UNESCAPED_UNICODE),
            ':location'        => $location,
            ':make_id'         => $makeId,
            ':body_type_id'    => $bodyTypeId,
            ':engine_capacity' => $engine,
            ':drive_train'     => $driveTrain,
            ':condition_score' => $condScore !== null ? (int)$condScore : null,
            ':emission'        => $emission,
            ':finance_info'    => $financeInfo,
            ':source_url'      => $sourceUrl,
            ':price_unit'      => $priceUnit,
            ':imported_at'     => $scrapedAt ? date('Y-m-d H:i:s', strtotime($scrapedAt)) : null,
        ]);

        $carId    = (int)$pdo->lastInsertId();
        $imgCount = 0;

        foreach ($raw['images'] ?? [] as $relPath) {
            // relPath is like "images/01.jpg" — resolve against the car's directory
            $srcPath = $carDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
            $url     = importImage($srcPath, $carId, $imgCount, $uploadCarsDir, $uploadCarsRel);

            if ($url) {
                $imgStmt = $pdo->prepare("INSERT INTO car_images (car_id, url, `order`, type) VALUES (?, ?, ?, 'PHOTO')");
                $imgStmt->execute([$carId, $url, $imgCount]);
                $imgCount++;
            } else {
                echo "  [WARN]  $dirName — image not found: $relPath\n";
            }
        }

        $pdo->commit();
        $success++;
        echo "[OK]    $dirName — car ID $carId, $imgCount image(s)\n";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = "$dirName — " . $e->getMessage();
        echo "[ERROR] $dirName — " . $e->getMessage() . "\n";
    }
}

// ─── Summary ──────────────────────────────────────────────────────────────────

echo "\n========================================\n";
echo "Import complete.\n";
echo "  Imported : $success\n";
echo "  Skipped  : $skipped\n";
echo "  Errors   : " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nError details:\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
