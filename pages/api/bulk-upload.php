<?php
/**
 * pages/api/bulk-upload.php
 * Secure API Endpoint for Bulk Car Ingestion
 * Requires X-API-KEY header for authentication.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// ─── 1. Authentication ────────────────────────────────────────────────────────

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['X-API-KEY'] ?? '';
$validApiKey = $_ENV['BULK_UPLOAD_API_KEY'] ?? '';

if (empty($validApiKey) || $apiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Invalid or missing API Key.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST.']);
    exit;
}

// ─── 2. Server Limit Checks ───────────────────────────────────────────────────

if (isPostSizeExceeded()) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Payload Too Large. Increase PHP post_max_size.']);
    exit;
}

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['zip_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ZIP upload failed.', 'code' => $code]);
    exit;
}

if (strtolower(pathinfo($_FILES['zip_file']['name'], PATHINFO_EXTENSION)) !== 'zip') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Only .zip archives are accepted.']);
    exit;
}

// ─── 3. Import Logic (Reused from import-process.php) ───────────────────────

// Value Mapping Functions (Localized here for self-containment)
function mapConditionScore(?string $score): ?string {
    if ($score === null || $score === '') return null;
    $n = (int)$score;
    if ($n > 10) $n = (int)round($n / 10);
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
        'SUV' => ['Q2','Q3','Q5','Q7','Q8','X1','X3','X5','X6','X7','GLC','GLE','GLS','GLK','CX-5','CX-30','CX5','CX30','RAV4','CR-V','CRV','TUCSON','SPORTAGE','TIGUAN','T-ROC','TANYUE','KODIAQ','KAROQ','FORESTER','OUTBACK','HIGHLANDER','EXPLORER','ESCAPE','EDGE','EQUINOX','TRAVERSE','PALISADE','TELLURIDE','SORENTO','SANTA FE','STELVIO','MACAN','CAYENNE','URUS','DEFENDER','DISCOVERY','RANGE ROVER','COUNTRYMAN','EPAO','TIGGO','HRS1','T600','YU7','ID.UNYX','ID.4','ID.6'],
        'VAN' => ['TRANSIT','SPRINTER','VITO','V-CLASS','SAVANA','CARNIVAL','ODYSSEY','SIENNA','CARAVELLE','MONPIKE'],
        'TRUCK' => ['F-150','F150','RANGER','TACOMA','TUNDRA','HILUX','NAVARA','AMAROK','SILVERADO','SIERRA','RAM'],
        'HATCHBACK' => ['GOLF','POLO','FIESTA','FOCUS','SWIFT','YARIS','CLIO','1 SERIES','M135I','BENBEN','LIANA'],
        'SEDAN' => ['CONTINENTAL','SUPERB','MAGOTAN','CHAIRMAN','B50','508','K3','K5','MX-5','D60','V6'],
    ];
    foreach ($map as $typeName => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($m, $kw)) {
                $stmt = $pdo->prepare("SELECT id FROM body_types WHERE UPPER(name) = ? LIMIT 1");
                $stmt->execute([$typeName]);
                $row = $stmt->fetch();
                if (!$row) {
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
    $translations = ['起亚'=>'Kia','大众'=>'Volkswagen','丰田'=>'Toyota','本田'=>'Honda','日产'=>'Nissan','宝马'=>'BMW','奔驰'=>'Mercedes-Benz','奥迪'=>'Audi','福特'=>'Ford','雪佛兰'=>'Chevrolet','别克'=>'Buick','凯迪拉克'=>'Cadillac','现代'=>'Hyundai','马自达'=>'Mazda','斯柯达'=>'Skoda','沃尔沃'=>'Volvo','雷克萨斯'=>'Lexus'];
    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $make)) {
        $lower = strtolower($make);
        foreach ($translations as $cn => $en) {
            if (str_contains($lower, $cn)) {
                $remaining = trim(str_ireplace($cn, '', $lower));
                return ['make' => $en, 'model' => ($remaining ? strtoupper($remaining) : null)];
            }
        }
    }
    return ['make' => $make, 'model' => null];
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

function getOrCreateMake(PDO $pdo, string $name): int {
    $stmt = $pdo->prepare("SELECT id FROM makes WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];
    $pdo->prepare("INSERT INTO makes (name) VALUES (?)")->execute([$name]);
    return (int)$pdo->lastInsertId();
}

function deleteDirectory(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

function findCarDirs(string $tmpDir): array {
    $dirs = [];
    $entries = array_diff(scandir($tmpDir), ['.', '..']);
    foreach ($entries as $entry) {
        $path = $tmpDir . $entry;
        if (!is_dir($path)) continue;
        if (file_exists($path . '/data.json')) {
            $dirs[] = $path;
        } else {
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

// ─── Extraction Process ───────────────────────────────────────────────────────

$tmpDir = UPLOAD_DIR . 'api_import_' . uniqid() . '/';
mkdir($tmpDir, 0755, true);

$zip = new ZipArchive();
if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
    rmdir($tmpDir);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Failed to open ZIP archive.']);
    exit;
}
$zip->extractTo($tmpDir);
$zip->close();

$db = getDB();
$carsUploadDir = UPLOAD_DIR . 'cars/';
if (!is_dir($carsUploadDir)) mkdir($carsUploadDir, 0755, true);

$imported = 0;
$skipped  = 0;
$errors   = [];
$log      = [];

$carDirs = findCarDirs($tmpDir);

if (empty($carDirs)) {
    deleteDirectory($tmpDir);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid car folders with data.json found.']);
    exit;
}

foreach ($carDirs as $carDir) {
    $dirName  = basename($carDir);
    $jsonFile = $carDir . '/data.json';
    $raw = json_decode(file_get_contents($jsonFile), true);
    if (!$raw) { $errors[] = "$dirName: Invalid JSON"; continue; }

    // Source URL dupe check
    $sourceUrl = $raw['metadata']['source_url'] ?? null;
    if ($sourceUrl) {
        $check = $db->prepare("SELECT id FROM cars WHERE source_url = ?");
        $check->execute([$sourceUrl]);
        if ($check->fetchColumn()) { $skipped++; continue; }
    }

    $translated = normalizeMake(trim($raw['make'] ?? ''));
    $make = $translated['make'];
    $model = trim($raw['model'] ?? '') ?: ($translated['model'] ?? '');
    $year = (int)($raw['year'] ?? 0);

    if (!$make || !$model || !$year) {
        $errors[] = "$dirName: Missing core fields (make/model/year)";
        continue;
    }

    try {
        $db->beginTransaction();
        $makeId = getOrCreateMake($db, $make);
        $slug = ensureUniqueSlug($db, generateSlug($make, $model, (string)$year));

        $stmt = $db->prepare("INSERT INTO cars (slug, make, model, year, price, mileage, vin, color, fuel_type, transmission, `condition`, description, features, location, status, make_id, body_type_id, engine_capacity, drive_train, condition_score, emission, finance_info, source_url, price_unit, imported_at, created_at, updated_at) VALUES (:slug, :make, :model, :year, :price, :mileage, :vin, :color, :fuel_type, :transmission, :condition, :description, :features, :location, 'AVAILABLE', :make_id, :body_type_id, :engine_capacity, :drive_train, :condition_score, :emission, :finance_info, :source_url, :price_unit, :imported_at, NOW(), NOW())");
        $stmt->execute([
            ':slug'            => $slug,
            ':make'            => $make,
            ':model'           => $model,
            ':year'            => $year,
            ':price'           => (float)($raw['price'] ?? 0),
            ':mileage'         => (int)($raw['mileage'] ?? 0),
            ':vin'             => $raw['vin'] ?? null,
            ':color'           => $raw['color'] ?? null,
            ':fuel_type'       => mapFuelType($raw['fuel_type'] ?? null) ?? inferFuelType($raw['engine'] ?? null, $raw['description'] ?? null, $raw['emission'] ?? null),
            ':transmission'    => mapTransmission($raw['transmission'] ?? null),
            ':condition'       => mapConditionScore($raw['condition_score'] ?? null),
            ':description'     => $raw['description'] ?? null,
            ':features'        => json_encode($raw['features'] ?? [], JSON_UNESCAPED_UNICODE),
            ':location'        => $raw['location'] ?? null,
            ':make_id'         => $makeId,
            ':body_type_id'    => inferBodyType($model, $db),
            ':engine_capacity' => $raw['engine'] ?? null,
            ':drive_train'     => $raw['drive_mode'] ?? null,
            ':condition_score' => isset($raw['condition_score']) ? (int)$raw['condition_score'] : null,
            ':emission'        => $raw['emission'] ?? null,
            ':finance_info'    => $raw['finance_info'] ?? null,
            ':source_url'      => $sourceUrl,
            ':price_unit'      => $raw['price_unit'] ?? 'CNY',
            ':imported_at'     => isset($raw['metadata']['scraped_at']) ? date('Y-m-d H:i:s', strtotime($raw['metadata']['scraped_at'])) : null,
        ]);

        $carId = (int)$db->lastInsertId();
        $imgCount = 0;
        foreach ($raw['images'] ?? [] as $relPath) {
            $srcPath = $carDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
            if (!file_exists($srcPath)) continue;
            $filename = time() . '_' . $carId . '_' . $imgCount . '_' . basename($srcPath);
            if (copy($srcPath, $carsUploadDir . $filename)) {
                $db->prepare("INSERT INTO car_images (car_id, url, `order`, type) VALUES (?, ?, ?, 'PHOTO')")->execute([$carId, 'uploads/cars/' . $filename, $imgCount]);
                $imgCount++;
            }
        }
        $db->commit();
        $imported++;
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errors[] = "$dirName: " . $e->getMessage();
    }
}

deleteDirectory($tmpDir);

echo json_encode([
    'success' => true,
    'results' => [
        'total'    => count($carDirs),
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors
    ]
]);
