<?php
/**
 * admin/cars/import-process.php - Bulk JSON/ZIP Import Engine
 * Accepts a ZIP of scraped car folders (each with data.json + images/).
 * Ports all logic from database/import_scraped.php into the web handler.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAuth();

if (!isAdminRole()) {
    setFlash('error', 'Insufficient clearance for bulk operations.');
    redirect('import.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('import.php');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Security integrity compromised.');
    redirect('import.php');
}

// ─── Validate ZIP upload ───────────────────────────────────────────────────────

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    ];
    $code = $_FILES['zip_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    setFlash('error', $uploadErrors[$code] ?? 'Upload failed with unknown error.');
    redirect('import.php');
}

if (strtolower(pathinfo($_FILES['zip_file']['name'], PATHINFO_EXTENSION)) !== 'zip') {
    setFlash('error', 'Only .zip archives are accepted.');
    redirect('import.php');
}

// ─── Extract ZIP to temp directory ────────────────────────────────────────────

$tmpDir = UPLOAD_DIR . 'tmp_import_' . uniqid() . '/';
mkdir($tmpDir, 0755, true);

$zip = new ZipArchive();
if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
    rmdir($tmpDir);
    setFlash('error', 'Failed to open ZIP archive. Ensure it is a valid .zip file.');
    redirect('import.php');
}
$zip->extractTo($tmpDir);
$zip->close();

// ─── Value Mapping Functions ───────────────────────────────────────────────────

function mapConditionScore(?string $score): ?string {
    if ($score === null || $score === '') return null;
    $n = (int)$score;
    if ($n > 10) $n = (int)round($n / 10); // normalise 0-100 → 0-10
    if ($n >= 9) return 'EXCELLENT';
    if ($n >= 7) return 'VERY_GOOD';
    if ($n >= 5) return 'GOOD';
    if ($n >= 1) return 'FAIR';
    return null;
}

function mapTransmission(?string $val): ?string {
    if (!$val) return null;
    $v = strtolower(trim($val));
    if (str_contains($v, 'cvt'))                   return 'CVT';
    if (str_contains($v, 'auto') || $v === 'at')   return 'AUTOMATIC';
    if (str_contains($v, 'manual') || $v === 'mt') return 'MANUAL';
    return null;
}

function mapFuelType(?string $val): ?string {
    if (!$val) return null;
    $v = strtolower(trim($val));
    if (str_contains($v, 'electric') || str_contains($v, 'ev'))    return 'ELECTRIC';
    if (str_contains($v, 'plugin') || str_contains($v, 'plug-in')) return 'PLUGIN_HYBRID';
    if (str_contains($v, 'hybrid'))                                 return 'HYBRID';
    if (str_contains($v, 'diesel'))                                 return 'DIESEL';
    if (str_contains($v, 'gasoline') || str_contains($v, 'petrol') || str_contains($v, 'gas')) return 'GASOLINE';
    return null;
}

function inferFuelType(?string $engine, ?string $description, ?string $emission): ?string {
    $h = strtolower(implode(' ', array_filter([$engine, $description, $emission])));
    if (str_contains($h, 'phev') || str_contains($h, 'plug-in') || str_contains($h, 'plugin')) return 'PLUGIN_HYBRID';
    if (str_contains($h, 'hybrid') || str_contains($h, 'hev'))                                 return 'HYBRID';
    if (str_contains($h, 'electric') || str_contains($h, ' ev ') || str_contains($h, 'bev'))   return 'ELECTRIC';
    if (str_contains($h, 'diesel'))                                                             return 'DIESEL';
    if ($engine && preg_match('/^\d+\.\d+[tl]$/i', trim($engine)))                             return 'GASOLINE';
    return null;
}

function inferBodyType(?string $model, PDO $pdo): ?int {
    if (!$model) return null;
    $m = strtoupper(trim($model));

    $map = [
        'SUV'       => ['Q2','Q3','Q5','Q7','Q8','X1','X3','X5','X6','X7','GLC','GLE','GLS','GLK',
                        'CX-5','CX-30','CX5','CX30','RAV4','CR-V','CRV','TUCSON','SPORTAGE',
                        'TIGUAN','T-ROC','TANYUE','KODIAQ','KAROQ','FORESTER','OUTBACK',
                        'HIGHLANDER','EXPLORER','ESCAPE','EDGE','EQUINOX','TRAVERSE',
                        'PALISADE','TELLURIDE','SORENTO','SANTA FE','STELVIO','MACAN',
                        'CAYENNE','URUS','DEFENDER','DISCOVERY','RANGE ROVER','COUNTRYMAN',
                        'EPAO','TIGGO','HRS1','T600','YU7','ID.UNYX','ID.4','ID.6'],
        'VAN'       => ['TRANSIT','SPRINTER','VITO','V-CLASS','SAVANA','CARNIVAL',
                        'ODYSSEY','SIENNA','CARAVELLE','MONPIKE'],
        'TRUCK'     => ['F-150','F150','RANGER','TACOMA','TUNDRA','HILUX','NAVARA',
                        'AMAROK','SILVERADO','SIERRA','RAM'],
        'HATCHBACK' => ['GOLF','POLO','FIESTA','FOCUS','SWIFT','YARIS','CLIO',
                        '1 SERIES','M135I','BENBEN','LIANA'],
        'SEDAN'     => ['CONTINENTAL','SUPERB','MAGOTAN','CHAIRMAN','B50','508',
                        'K3','K5','MX-5','D60','V6'],
    ];

    foreach ($map as $typeName => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($m, $kw)) {
                $stmt = $pdo->prepare("SELECT id FROM body_types WHERE UPPER(name) = ? LIMIT 1");
                $stmt->execute([$typeName]);
                $row = $stmt->fetch();
                if (!$row) {
                    // Try partial match (e.g. "Mini Van" contains "VAN")
                    $stmt2 = $pdo->prepare("SELECT id FROM body_types WHERE UPPER(name) LIKE ? LIMIT 1");
                    $stmt2->execute(["%$typeName%"]);
                    $row = $stmt2->fetch();
                }
                return $row ? (int)$row['id'] : null;
            }
        }
    }
    return null;
}

function normalizeMake(string $make): array {
    $translations = [
        '起亚' => 'Kia', '大众' => 'Volkswagen', '丰田' => 'Toyota',
        '本田' => 'Honda', '日产' => 'Nissan', '宝马' => 'BMW',
        '奔驰' => 'Mercedes-Benz', '奥迪' => 'Audi', '福特' => 'Ford',
        '雪佛兰' => 'Chevrolet', '别克' => 'Buick', '凯迪拉克' => 'Cadillac',
        '现代' => 'Hyundai', '马自达' => 'Mazda', '斯柯达' => 'Skoda',
        '沃尔沃' => 'Volvo', '雷克萨斯' => 'Lexus',
    ];
    $modelMap = ['k3' => 'K3', 'k5' => 'K5'];

    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $make)) {
        $lower = strtolower($make);
        foreach ($translations as $cn => $en) {
            if (str_contains($lower, $cn)) {
                $remaining = trim(str_ireplace($cn, '', $lower));
                $model = $remaining ? ($modelMap[$remaining] ?? strtoupper($remaining)) : null;
                return ['make' => $en, 'model' => $model];
            }
        }
    }
    return ['make' => $make, 'model' => null];
}

function cleanModel(string $model): string {
    return trim(preg_replace('/\s*\(.*$/', '', $model));
}

function extractYearFromDirName(string $dir): ?int {
    return preg_match('/\b(19|20)\d{2}\b/', $dir, $m) ? (int)$m[0] : null;
}

function extractYearFromAge(?string $age, ?string $scrapedAt): ?int {
    if (!$age || !$scrapedAt) return null;
    if (!preg_match('/(\d+)\s+year/i', $age, $m)) return null;
    return (int)date('Y', strtotime($scrapedAt)) - (int)$m[1];
}

function extractModelFromDirName(string $dirName, string $make): string {
    $slug     = strtolower($dirName);
    $makeSlug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $make));
    $slug     = preg_replace('/^' . preg_quote($makeSlug, '/') . '-?/', '', $slug);
    $slug     = preg_replace('/\b(19|20)\d{2}\b/', '', $slug);
    $slug     = preg_replace('/\b\d+\.\d+[tl]?\b/', '', $slug);
    $slug     = preg_replace('/\(.*?\)/', '', $slug);
    return ucwords(trim(preg_replace('/[-\s]+/', ' ', $slug)));
}

function getOrCreateMake(PDO $pdo, string $name): int {
    $stmt = $pdo->prepare("SELECT id FROM makes WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];
    $pdo->prepare("INSERT INTO makes (name) VALUES (?)")->execute([$name]);
    return (int)$pdo->lastInsertId();
}

function generateSlug(string $make, string $model, string $year): string {
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', "$make-$model-$year")));
    return trim($base, '-') . '_' . time();
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

// ─── Discover car directories in the extracted ZIP ────────────────────────────
// The ZIP may have a single root folder (e.g. cars/) or car folders directly at root.

function findCarDirs(string $tmpDir): array {
    $dirs = [];
    $entries = array_diff(scandir($tmpDir), ['.', '..']);

    foreach ($entries as $entry) {
        $path = $tmpDir . $entry;
        if (!is_dir($path)) continue;

        // If this folder contains data.json it IS a car folder
        if (file_exists($path . '/data.json')) {
            $dirs[] = $path;
        } else {
            // It might be a wrapper folder (e.g. "cars/") — look one level deeper
            $sub = array_diff(scandir($path), ['.', '..']);
            foreach ($sub as $s) {
                $subPath = $path . '/' . $s;
                if (is_dir($subPath) && file_exists($subPath . '/data.json')) {
                    $dirs[] = $subPath;
                }
            }
        }
    }
    sort($dirs);
    return $dirs;
}

// ─── Main import loop ──────────────────────────────────────────────────────────

$db          = getDB();
$carsUploadDir = UPLOAD_DIR . 'cars/';
if (!is_dir($carsUploadDir)) mkdir($carsUploadDir, 0755, true);

$imported = 0;
$skipped  = 0;
$errors   = [];
$log      = [];

$carDirs = findCarDirs($tmpDir);

if (empty($carDirs)) {
    deleteDirectory($tmpDir);
    setFlash('error', 'No valid car folders found in the ZIP. Each folder must contain a data.json file.');
    redirect('import.php');
}

foreach ($carDirs as $carDir) {
    $dirName  = basename($carDir);
    $jsonFile = $carDir . '/data.json';

    $raw = json_decode(file_get_contents($jsonFile), true);
    if (!$raw) {
        $errors[] = "$dirName — invalid JSON";
        $log[]    = "[ERROR] $dirName — invalid JSON";
        continue;
    }

    // Deduplication by source_url
    $sourceUrl = $raw['metadata']['source_url'] ?? null;
    if ($sourceUrl) {
        $check = $db->prepare("SELECT id FROM cars WHERE source_url = ?");
        $check->execute([$sourceUrl]);
        if ($check->fetchColumn()) {
            $skipped++;
            $log[] = "[SKIP]  $dirName — already imported";
            continue;
        }
    }

    // ── Field extraction ────────────────────────────────────────────────────
    $translated = normalizeMake(trim($raw['make'] ?? ''));
    $make       = $translated['make'];
    $model      = trim($raw['model'] ?? '') ?: ($translated['model'] ?? '');
    $year       = (int)($raw['year'] ?? 0);

    if ($model) $model = cleanModel($model);
    if (!$year) $year  = extractYearFromDirName($dirName) ?? 0;
    if (!$year) $year  = extractYearFromAge($raw['age'] ?? null, $raw['metadata']['scraped_at'] ?? null) ?? 0;
    if (!$model && $make) $model = extractModelFromDirName($dirName, $make);

    if (!$make || !$model || !$year) {
        $errors[] = "$dirName — missing make/model/year (make=$make model=$model year=$year)";
        $log[]    = "[ERROR] $dirName — missing make/model/year";
        continue;
    }

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
    $condScore   = isset($raw['condition_score']) && $raw['condition_score'] !== null
                   ? (string)$raw['condition_score'] : null;
    $financeInfo = ($raw['finance_info'] ?? null) ?: null;
    $scrapedAt   = $raw['metadata']['scraped_at'] ?? null;

    $conditionEnum    = mapConditionScore($condScore);
    $transmissionEnum = mapTransmission($raw['transmission'] ?? null);
    $fuelTypeEnum     = mapFuelType($raw['fuel_type'] ?? null)
                        ?? inferFuelType($engine, $description, $emission);
    $bodyTypeRaw      = ($raw['body_type'] ?? null) ?: null;
    $bodyTypeId       = $bodyTypeRaw
                        ? (function() use ($db, $bodyTypeRaw) {
                              $s = $db->prepare("SELECT id FROM body_types WHERE LOWER(name) = LOWER(?) LIMIT 1");
                              $s->execute([$bodyTypeRaw]);
                              $r = $s->fetch();
                              return $r ? (int)$r['id'] : null;
                          })()
                        : inferBodyType($model, $db);

    try {
        $db->beginTransaction();

        $makeId = getOrCreateMake($db, $make);
        $slug   = ensureUniqueSlug($db, generateSlug($make, $model, (string)$year));

        $stmt = $db->prepare("
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

        $carId    = (int)$db->lastInsertId();
        $imgCount = 0;

        foreach ($raw['images'] ?? [] as $relPath) {
            $srcPath = $carDir . DIRECTORY_SEPARATOR
                       . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);

            if (!file_exists($srcPath)) {
                $log[] = "  [WARN]  $dirName — image not found: $relPath";
                continue;
            }

            $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;

            $filename = time() . '_' . $carId . '_' . $imgCount . '_' . basename($srcPath);
            $destPath = $carsUploadDir . $filename;

            if (copy($srcPath, $destPath)) {
                $db->prepare("INSERT INTO car_images (car_id, url, `order`, type) VALUES (?, ?, ?, 'PHOTO')")
                   ->execute([$carId, 'uploads/cars/' . $filename, $imgCount]);
                $imgCount++;
            } else {
                $log[] = "  [WARN]  $dirName — failed to copy image: $relPath";
            }
        }

        $db->commit();
        $imported++;
        $log[] = "[OK]    $dirName — car ID $carId, $imgCount image(s)";

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errors[] = "$dirName — " . $e->getMessage();
        $log[]    = "[ERROR] $dirName — " . $e->getMessage();
    }
}

// ─── Cleanup temp directory ────────────────────────────────────────────────────

deleteDirectory($tmpDir);

// ─── Store results and redirect ────────────────────────────────────────────────

setFlash('import_results', json_encode([
    'imported' => $imported,
    'skipped'  => $skipped,
    'errors'   => $errors,
    'log'      => $log,
]));

$total = count($carDirs);
if (empty($errors)) {
    setFlash('success', "Import complete: $imported of $total vehicles imported, $skipped skipped.");
} else {
    setFlash('success', "Import finished: $imported imported, $skipped skipped, " . count($errors) . " error(s). See report below.");
}

redirect('import.php');

// ─── Helpers ───────────────────────────────────────────────────────────────────

function deleteDirectory(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
