<?php
/**
 * admin/cars/import-process.php - Bulk Import Engine
 * Parses CSV, extracts ZIP, inserts car records with images
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAuth();

if (!isAdminRole()) {
    setFlash('error', 'Insufficient clearance for bulk operations.');
    redirect('../dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('import.php');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Security integrity compromised.');
    redirect('import.php');
}

// --- Validate CSV Upload ---
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    setFlash('error', 'CSV data file is required. Please select a valid .csv file.');
    redirect('import.php');
}

$csvMime = mime_content_type($_FILES['csv_file']['tmp_name']);
$csvExt = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
if ($csvExt !== 'csv') {
    setFlash('error', 'Invalid file type. Only .csv files are accepted for vehicle data.');
    redirect('import.php');
}

// --- Handle optional ZIP Upload ---
$zipExtracted = false;
$tmpExtractDir = UPLOAD_DIR . 'tmp_import_' . uniqid() . '/';

if (isset($_FILES['zip_file']) && $_FILES['zip_file']['error'] === UPLOAD_ERR_OK) {
    $zipExt = strtolower(pathinfo($_FILES['zip_file']['name'], PATHINFO_EXTENSION));
    if ($zipExt !== 'zip') {
        setFlash('error', 'Invalid archive type. Only .zip files are accepted for images.');
        redirect('import.php');
    }

    $zip = new ZipArchive();
    if ($zip->open($_FILES['zip_file']['tmp_name']) === true) {
        if (!is_dir($tmpExtractDir)) {
            mkdir($tmpExtractDir, 0755, true);
        }
        $zip->extractTo($tmpExtractDir);
        $zip->close();
        $zipExtracted = true;
    } else {
        setFlash('error', 'Failed to open ZIP archive. Ensure it is a valid zip file.');
        redirect('import.php');
    }
}

// --- Parse CSV and Import ---
$db = getDB();
$handle = fopen($_FILES['csv_file']['tmp_name'], 'r');

if ($handle === false) {
    setFlash('error', 'Failed to read the CSV file.');
    redirect('import.php');
}

// Read header row
$headers = fgetcsv($handle);
if ($headers === false) {
    fclose($handle);
    setFlash('error', 'CSV file is empty or malformed.');
    redirect('import.php');
}

// Normalize headers (trim whitespace, lowercase)
$headers = array_map(function($h) {
    return strtolower(trim($h));
}, $headers);

// Validate required columns
$requiredCols = ['make', 'model', 'year', 'price', 'mileage'];
foreach ($requiredCols as $col) {
    if (!in_array($col, $headers)) {
        fclose($handle);
        setFlash('error', "Missing required CSV column: '{$col}'. Please use the template.");
        redirect('import.php');
    }
}

$carsImported = 0;
$imagesImported = 0;
$errors = [];
$rowNum = 1;

// Ensure upload directory exists
$carsUploadDir = UPLOAD_DIR . 'cars/';
if (!is_dir($carsUploadDir)) {
    mkdir($carsUploadDir, 0755, true);
}

$db->beginTransaction();

try {
    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;

        // Skip completely empty rows
        if (count(array_filter($row)) === 0) continue;

        // Map headers to values
        $data = [];
        foreach ($headers as $i => $header) {
            $data[$header] = isset($row[$i]) ? trim($row[$i]) : '';
        }

        // Validate required fields
        if (empty($data['make']) || empty($data['model']) || empty($data['year']) || empty($data['price']) || empty($data['mileage'])) {
            $errors[] = "Row {$rowNum}: Missing required field(s) — skipped.";
            continue;
        }

        // Validate numeric fields
        if (!is_numeric($data['price']) || !is_numeric($data['mileage'])) {
            $errors[] = "Row {$rowNum}: Price/Mileage must be numeric — skipped.";
            continue;
        }

        // Generate slug
        $slug = strtolower($data['year'] . '-' . $data['make'] . '-' . $data['model']);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check for duplicate slug
        $checkStmt = $db->prepare("SELECT id FROM cars WHERE slug = ?");
        $checkStmt->execute([$slug]);
        if ($checkStmt->fetch()) {
            $slug .= '-' . uniqid();
        }

        // Prepare optional fields with null fallback
        $vin = !empty($data['vin']) ? $data['vin'] : null;
        $color = !empty($data['color']) ? $data['color'] : null;
        $fuel_type = !empty($data['fuel_type']) ? strtoupper($data['fuel_type']) : null;
        $transmission = !empty($data['transmission']) ? strtoupper($data['transmission']) : null;
        $body_type = !empty($data['body_type']) ? strtoupper($data['body_type']) : null;
        $condition = !empty($data['condition']) ? strtoupper($data['condition']) : null;
        $description = !empty($data['description']) ? $data['description'] : null;

        // Features as JSON
        $features = null;
        if (!empty($data['features'])) {
            $featureList = array_map('trim', explode(',', $data['features']));
            $features = json_encode($featureList);
        }

        // Insert car record
        $stmt = $db->prepare("
            INSERT INTO cars (slug, make, model, year, price, mileage, vin, color, fuel_type, transmission, body_type, `condition`, description, features, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AVAILABLE')
        ");
        $stmt->execute([
            $slug,
            $data['make'],
            $data['model'],
            intval($data['year']),
            floatval($data['price']),
            intval($data['mileage']),
            $vin,
            $color,
            $fuel_type,
            $transmission,
            $body_type,
            $condition,
            $description,
            $features
        ]);

        $carId = $db->lastInsertId();
        $carsImported++;

        // --- Process images from ZIP ---
        $imageFolder = $data['image_folder'] ?? '';
        if ($zipExtracted && !empty($imageFolder)) {
            $folderPath = $tmpExtractDir . $imageFolder;

            // Also check with trailing slash or inside a nested directory
            if (!is_dir($folderPath)) {
                // Try to find it case-insensitively
                $found = false;
                if (is_dir($tmpExtractDir)) {
                    foreach (scandir($tmpExtractDir) as $dir) {
                        if (strtolower($dir) === strtolower($imageFolder) && is_dir($tmpExtractDir . $dir)) {
                            $folderPath = $tmpExtractDir . $dir;
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $errors[] = "Row {$rowNum}: Image folder '{$imageFolder}' not found in ZIP — images skipped.";
                    continue;
                }
            }

            // Scan for valid image files
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $imageFiles = [];
            foreach (scandir($folderPath) as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExts)) {
                    $imageFiles[] = $file;
                }
            }

            // Sort for consistent ordering
            sort($imageFiles);

            $order = 0;
            foreach ($imageFiles as $imgFile) {
                // Generate unique filename
                $newFilename = $slug . '-' . $order . '-' . uniqid() . '.' . strtolower(pathinfo($imgFile, PATHINFO_EXTENSION));
                $destPath = $carsUploadDir . $newFilename;
                $srcPath = $folderPath . '/' . $imgFile;

                if (copy($srcPath, $destPath)) {
                    $imageUrl = UPLOAD_URL . 'cars/' . $newFilename;
                    $imgStmt = $db->prepare("INSERT INTO car_images (car_id, url, `order`, type) VALUES (?, ?, ?, 'PHOTO')");
                    $imgStmt->execute([$carId, $imageUrl, $order]);
                    $imagesImported++;
                    $order++;
                } else {
                    $errors[] = "Row {$rowNum}: Failed to copy image '{$imgFile}'.";
                }
            }
        }
    }

    $db->commit();
    fclose($handle);

    // --- Cleanup tmp directory ---
    if ($zipExtracted && is_dir($tmpExtractDir)) {
        deleteDirectory($tmpExtractDir);
    }

    // Build result message
    $msg = "Import complete: {$carsImported} vehicles and {$imagesImported} images processed successfully.";
    if (!empty($errors)) {
        $msg .= ' (' . count($errors) . ' warnings: ' . implode(' | ', array_slice($errors, 0, 5)) . ')';
    }
    setFlash('success', $msg);
    redirect('index.php');

} catch (Exception $e) {
    $db->rollBack();
    fclose($handle);

    // Cleanup on failure
    if ($zipExtracted && is_dir($tmpExtractDir)) {
        deleteDirectory($tmpExtractDir);
    }

    setFlash('error', 'Import failed: ' . $e->getMessage());
    redirect('import.php');
}

/**
 * Recursively delete a directory and its contents
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
