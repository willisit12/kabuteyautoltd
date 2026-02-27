<?php
/**
 * admin/edit-car.php - Specification Editor
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

$id = intval($_GET['id'] ?? 0);
$car = getCarById($id);

if (!$car) {
    setFlash('error', 'Masterpiece signature not found.');
    redirect('../dashboard.php');
}

$error = getFlash('error');
$success = getFlash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
        redirect('edit.php?id=' . $id);
    }

    $data = [
        'make' => clean($_POST['make'] ?? ''),
        'model' => clean($_POST['model'] ?? ''),
        'year' => intval($_POST['year'] ?? 0),
        'price' => floatval($_POST['price'] ?? 0),
        'mileage' => intval($_POST['mileage'] ?? 0),
        'vin' => clean($_POST['vin'] ?? null),
        'color' => clean($_POST['color'] ?? ''),
        'fuel_type' => $_POST['fuel_type'] ?? 'GASOLINE',
        'transmission' => $_POST['transmission'] ?? 'AUTOMATIC',
        'body_type' => $_POST['body_type'] ?? 'SEDAN',
        'condition' => $_POST['condition'] ?? 'EXCELLENT',
        'description' => clean($_POST['description'] ?? ''),
        'features' => json_encode(array_filter(array_map('trim', explode(',', $_POST['features'] ?? '')))),
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'walkaround_video_url' => clean($_POST['walkaround_video_url'] ?? ''),
        'location' => clean($_POST['location'] ?? 'Toronto, ON'),
        'status' => clean($_POST['status'] ?? 'AVAILABLE'),
        'id' => $id
    ];

    if (empty($data['make']) || empty($data['model']) || $data['year'] < 1900 || $data['price'] < 0) {
        setFlash('error', 'Required specifications are incomplete or invalid.');
        redirect('edit.php?id=' . $id);
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE cars SET 
                         make = :make, 
                         model = :model, 
                         year = :year, 
                         price = :price, 
                         mileage = :mileage, 
                         vin = :vin,
                         color = :color,
                         fuel_type = :fuel_type, 
                         transmission = :transmission, 
                         body_type = :body_type,
                         `condition` = :condition,
                         description = :description, 
                         features = :features,
                         featured = :featured, 
                         walkaround_video_url = :walkaround_video_url,
                         location = :location,
                         status = :status 
                         WHERE id = :id");
    $stmt->execute($data);

    // Handle new image uploads
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        $files = $_FILES['images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === 0) {
                $targetDir = "../../uploads/cars/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileName = time() . '_' . $i . '_' . basename($files['name'][$i]);
                $targetPath = $targetDir . $fileName;
                
                if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                    $url = 'uploads/cars/' . $fileName;
                    $stmt = $db->prepare("INSERT INTO car_images (car_id, url, `order`) VALUES (?, ?, ?)");
                    $stmt->execute([$id, $url, 100 + $i]);
                }
            }
        }
    }

    setFlash('success', 'Specifications updated successfully.');
    redirect('edit.php?id=' . $id);
}

ob_start();
?>

<form method="POST" enctype="multipart/form-data" class="max-w-5xl mx-auto space-y-12 pb-24 px-4">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

    <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] flex items-center gap-4 text-sm font-bold">
            <i class="fas fa-check-circle text-xl"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] flex items-center gap-4 text-sm font-bold animate-pulse">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-12">
            <!-- Section: Primary Identification -->
            <div class="glass p-10 rounded-[3rem] border border-border/50 relative overflow-hidden shadow-xl">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i class="fas fa-fingerprint text-6xl"></i>
                </div>
                <h3 class="text-xl font-black text-foreground tracking-tighter uppercase mb-8 flex items-center gap-3">
                    <span class="w-2 h-2 bg-accent rounded-full"></span>
                    Primary Identification
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Manufacturer (Make)</label>
                        <input type="text" name="make" required value="<?php echo clean($car['make']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Blueprint (Model)</label>
                        <input type="text" name="model" required value="<?php echo clean($car['model']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Production Year</label>
                        <input type="number" name="year" required min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo intval($car['year']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Vehicle Identification (VIN)</label>
                        <input type="text" name="vin" maxlength="17" value="<?php echo clean($car['vin'] ?? ''); ?>" placeholder="17-Digit VIN"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                </div>
            </div>

            <!-- Section: Technical Soul -->
            <div class="glass p-10 rounded-[3rem] border border-border/50 relative overflow-hidden shadow-xl">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i class="fas fa-engine text-6xl text-foreground"></i>
                </div>
                <h3 class="text-xl font-black text-foreground tracking-tighter uppercase mb-8 flex items-center gap-3">
                    <span class="w-2 h-2 bg-accent rounded-full"></span>
                    Technical Composition
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Valuation (Price $)</label>
                        <input type="number" name="price" required step="1" value="<?php echo floatval($car['price']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Distance Travelled (Miles)</label>
                        <input type="number" name="mileage" required value="<?php echo intval($car['mileage']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Exterior Shade (Color)</label>
                        <input type="text" name="color" value="<?php echo clean($car['color'] ?? ''); ?>" placeholder="e.g. Guards Red"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Body Architecture</label>
                        <select name="body_type" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="SEDAN" <?php echo $car['body_type'] === 'SEDAN' ? 'selected' : ''; ?>>Exquisite Sedan</option>
                            <option value="SUV" <?php echo $car['body_type'] === 'SUV' ? 'selected' : ''; ?>>Commanding SUV</option>
                            <option value="COUPE" <?php echo $car['body_type'] === 'COUPE' ? 'selected' : ''; ?>>Performance Coupe</option>
                            <option value="CONVERTIBLE" <?php echo $car['body_type'] === 'CONVERTIBLE' ? 'selected' : ''; ?>>Grand Convertible</option>
                            <option value="HATCHBACK" <?php echo $car['body_type'] === 'HATCHBACK' ? 'selected' : ''; ?>>Sport Hatchback</option>
                            <option value="TRUCK" <?php echo $car['body_type'] === 'TRUCK' ? 'selected' : ''; ?>>Rugged Truck</option>
                            <option value="VAN" <?php echo $car['body_type'] === 'VAN' ? 'selected' : ''; ?>>Luxury Van</option>
                            <option value="WAGON" <?php echo $car['body_type'] === 'WAGON' ? 'selected' : ''; ?>>Sport Wagon</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Preservation State (Condition)</label>
                        <select name="condition" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="EXCELLENT" <?php echo $car['condition'] === 'EXCELLENT' ? 'selected' : ''; ?>>Pristine (Excellent)</option>
                            <option value="VERY_GOOD" <?php echo $car['condition'] === 'VERY_GOOD' ? 'selected' : ''; ?>>Superior (Very Good)</option>
                            <option value="GOOD" <?php echo $car['condition'] === 'GOOD' ? 'selected' : ''; ?>>Refined (Good)</option>
                            <option value="FAIR" <?php echo $car['condition'] === 'FAIR' ? 'selected' : ''; ?>>Functional (Fair)</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Drive Philosophy (Transmission)</label>
                        <select name="transmission" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="AUTOMATIC" <?php echo ($car['transmission'] === 'AUTOMATIC' || $car['transmission'] === 'Automatic') ? 'selected' : ''; ?>>Precision Automatic</option>
                            <option value="MANUAL" <?php echo ($car['transmission'] === 'MANUAL' || $car['transmission'] === 'Manual') ? 'selected' : ''; ?>>Engaging Manual</option>
                            <option value="CVT" <?php echo $car['transmission'] === 'CVT' ? 'selected' : ''; ?>>Seamless CVT</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Energy Source (Fuel Type)</label>
                        <select name="fuel_type" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="GASOLINE" <?php echo ($car['fuel_type'] === 'GASOLINE' || $car['fuel_type'] === 'Gasoline') ? 'selected' : ''; ?>>High-Octane Gasoline</option>
                            <option value="DIESEL" <?php echo ($car['fuel_type'] === 'DIESEL' || $car['fuel_type'] === 'Diesel') ? 'selected' : ''; ?>>Industrial Diesel</option>
                            <option value="ELECTRIC" <?php echo ($car['fuel_type'] === 'ELECTRIC' || $car['fuel_type'] === 'Electric') ? 'selected' : ''; ?>>Pure Propulsion (EV)</option>
                            <option value="HYBRID" <?php echo ($car['fuel_type'] === 'HYBRID' || $car['fuel_type'] === 'Hybrid') ? 'selected' : ''; ?>>Hybrid Harmony</option>
                            <option value="PLUGIN_HYBRID" <?php echo $car['fuel_type'] === 'PLUGIN_HYBRID' ? 'selected' : ''; ?>>Plug-in Efficiency</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Availability Status</label>
                        <select name="status" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="AVAILABLE" <?php echo $car['status'] === 'AVAILABLE' ? 'selected' : ''; ?>>Live Collection</option>
                            <option value="SOLD" <?php echo $car['status'] === 'SOLD' ? 'selected' : ''; ?>>Acquired / Sold</option>
                            <option value="RESERVED" <?php echo $car['status'] === 'RESERVED' ? 'selected' : ''; ?>>Reserved / Pending</option>
                            <option value="ARCHIVED" <?php echo $car['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived / Hidden</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Asset Location</label>
                        <input type="text" name="location" value="<?php echo clean($car['location'] ?? 'Toronto, ON'); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Cinematic Walkaround (Video URL)</label>
                        <input type="url" name="walkaround_video_url" value="<?php echo clean($car['walkaround_video_url'] ?? ''); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-8 px-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="featured" class="sr-only peer" <?php echo $car['featured'] ? 'checked' : ''; ?>>
                        <div class="w-14 h-7 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-accent shadow-sm"></div>
                        <span class="ms-3 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Showcase Feature</span>
                    </label>
                </div>

                <div class="mt-10 space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Narrative (Description)</label>
                    <textarea name="description" rows="5" class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-3xl focus:ring-2 focus:ring-accent focus:border-accent transition font-medium italic outline-none"><?php echo clean($car['description']); ?></textarea>
                </div>

                <div class="mt-8 space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Key Highlights (Comma Separated Features)</label>
                    <?php 
                    $featuresList = '';
                    if (!empty($car['features'])) {
                        $featuresArr = json_decode($car['features'], true);
                        if (is_array($featuresArr)) {
                            $featuresList = implode(', ', $featuresArr);
                        }
                    }
                    ?>
                    <input type="text" name="features" value="<?php echo clean($featuresList); ?>" placeholder="e.g. Leather Seats, Panoramic Sunroof"
                           class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <!-- Manage Existing Images -->
            <?php if (!empty($car['images'])): ?>
            <div class="glass p-10 rounded-[3rem] border border-border/50 relative overflow-hidden shadow-xl">
                <h3 class="text-xl font-black text-foreground tracking-tighter uppercase mb-8 flex items-center gap-3">
                    <span class="w-2 h-2 bg-accent rounded-full"></span>
                    Manage Assets
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <?php foreach ($car['images'] as $image): ?>
                    <div class="group relative aspect-video rounded-2xl overflow-hidden border border-border/50 hover:border-accent transition-all">
                        <img src="../../<?php echo $image['url']; ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-4 transition-all">
                            <a href="delete-image.php?id=<?php echo $image['id']; ?>&car_id=<?php echo $id; ?>" 
                               onclick="return confirm('Obliterate this asset?');"
                               class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white hover:scale-110 transition-transform">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <span class="text-[8px] font-black uppercase tracking-widest text-white/60">Asset ID: <?php echo $image['id']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Content (Media & Actions) -->
        <div class="space-y-12">
            <div class="glass p-8 rounded-[3rem] border border-border/50 shadow-xl">
                <h3 class="text-lg font-black text-foreground tracking-tighter uppercase mb-6 flex items-center gap-3">
                    <i class="fas fa-plus-circle text-accent"></i>
                    Append Visuals
                </h3>
                
                <div id="drop-zone" class="border-2 border-dashed border-border/50 rounded-[2rem] p-8 text-center hover:border-accent hover:bg-accent/5 transition-all cursor-pointer group">
                    <input type="file" name="images[]" id="file-input" multiple accept="image/*" class="hidden">
                    <div class="w-16 h-16 bg-accent/10 rounded-2xl flex items-center justify-center text-accent mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-images text-2xl"></i>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-foreground mb-1">Upload New Assets</p>
                    <p class="text-[8px] font-bold text-muted-foreground uppercase tracking-widest px-4">Assets will be appended to collection</p>
                </div>

                <div id="preview-grid" class="grid grid-cols-2 gap-4 mt-8">
                    <!-- Previews will go here -->
                </div>
            </div>

            <!-- Action Controls -->
            <div class="sticky top-32 space-y-4">
                <button type="submit" class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Synchronize Blueprint</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
                <a href="../dashboard.php" class="block w-full text-center py-5 rounded-[2rem] border border-border text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:text-foreground hover:bg-muted transition-all">
                    Discard Changes
                </a>
                <div class="h-px bg-border/50 my-6"></div>
                <a href="delete.php?id=<?php echo $id; ?>" onclick="return confirm('Obliterate this masterpiece from existence?');"
                   class="block w-full text-center py-4 rounded-[1.5rem] bg-red-500/10 text-[9px] font-black uppercase tracking-widest text-red-500 hover:bg-red-500 hover:text-white transition-all">
                    <i class="fas fa-trash-alt mr-2"></i>Permanently Archive
                </a>
            </div>
        </div>
    </div>
</form>

<script>
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const previewGrid = document.getElementById('preview-grid');

    if (dropZone) {
        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-accent', 'bg-accent/5');
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-accent', 'bg-accent/5');
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-accent', 'bg-accent/5');
            handleFiles(e.dataTransfer.files);
        });
    }

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'aspect-square relative rounded-2xl overflow-hidden border border-border/50 group hover:border-accent transition-all';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all">
                         <i class="fas fa-times text-white cursor-pointer hover:scale-125 transition-transform" onclick="this.parentElement.parentElement.remove()"></i>
                    </div>
                `;
                previewGrid.appendChild(div);
                
                if (typeof gsap !== 'undefined') {
                    gsap.from(div, {
                        scale: 0.8,
                        opacity: 0,
                        duration: 0.5,
                        ease: "back.out(1.7)"
                    });
                }
            };
            reader.readAsDataURL(file);
        });
    }
</script>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Blueprint Synchronizer');
?>