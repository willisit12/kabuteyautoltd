<?php
/**
 * admin/add-car.php - Specification Editor
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

$error = getFlash('error');
$success = getFlash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
        redirect('add.php');
    }

    $make = clean($_POST['make'] ?? '');
    $model = clean($_POST['model'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    
    // Generate unique slug: make-model-year_timestamp
    $slug_base = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $make . '-' . $model . '-' . $year)));
    $slug = $slug_base . '_' . time();

    $data = [
        'slug' => $slug,
        'make' => $make,
        'model' => $model,
        'year' => $year,
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
        'status' => 'AVAILABLE',
        'view_count' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];

    if (empty($data['make']) || empty($data['model']) || $data['year'] < 1900 || $data['price'] < 0) {
        setFlash('error', 'Required specifications are incomplete or invalid.');
        redirect('add.php');
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO cars (slug, make, model, year, price, mileage, vin, color, fuel_type, transmission, body_type, `condition`, description, features, featured, walkaround_video_url, location, status, view_count, created_at) 
                             VALUES (:slug, :make, :model, :year, :price, :mileage, :vin, :color, :fuel_type, :transmission, :body_type, :condition, :description, :features, :featured, :walkaround_video_url, :location, :status, :view_count, :created_at)");
        $stmt->execute($data);
        $carId = $db->lastInsertId();

        // Handle image uploads
        if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
            $files = $_FILES['images'];
            $uploadTime = time();
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === 0) {
                    $targetDir = "../../uploads/cars/";
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                    
                    $fileName = $uploadTime . '_' . $i . '_' . basename($files['name'][$i]);
                    $targetPath = $targetDir . $fileName;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                        $url = 'uploads/cars/' . $fileName;
                        $stmt = $db->prepare("INSERT INTO car_images (car_id, url, `order`) VALUES (?, ?, ?)");
                        $stmt->execute([$carId, $url, $i]);
                    }
                }
            }
        }

        setFlash('success', 'Car added to collection.');
        redirect('../dashboard.php');
    } catch (PDOException $e) {
        setFlash('error', 'Intelligence breach: ' . $e->getMessage());
        redirect('add.php');
    }
}

ob_start();
?>

<form method="POST" enctype="multipart/form-data" class="max-w-5xl mx-auto space-y-12 pb-24 px-4">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

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
                        <input type="text" name="make" required placeholder="e.g. Porsche"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Blueprint (Model)</label>
                        <input type="text" name="model" required placeholder="e.g. 911 Carrera"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Production Year</label>
                        <input type="number" name="year" required min="1900" max="<?php echo date('Y') + 1; ?>" placeholder="2024"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Vehicle Identification (VIN)</label>
                        <input type="text" name="vin" maxlength="17" placeholder="17-Digit VIN"
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
                        <input type="number" name="price" required step="1" placeholder="125000"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Distance Travelled (Miles)</label>
                        <input type="number" name="mileage" required placeholder="5200"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Exterior Shade (Color)</label>
                        <input type="text" name="color" placeholder="e.g. Guards Red"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Body Architecture</label>
                        <select name="body_type" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="SEDAN">Exquisite Sedan</option>
                            <option value="SUV">Commanding SUV</option>
                            <option value="COUPE">Performance Coupe</option>
                            <option value="CONVERTIBLE">Grand Convertible</option>
                            <option value="HATCHBACK">Sport Hatchback</option>
                            <option value="TRUCK">Rugged Truck</option>
                            <option value="VAN">Luxury Van</option>
                            <option value="WAGON">Sport Wagon</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Preservation State (Condition)</label>
                        <select name="condition" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="EXCELLENT">Pristine (Excellent)</option>
                            <option value="VERY_GOOD">Superior (Very Good)</option>
                            <option value="GOOD">Refined (Good)</option>
                            <option value="FAIR">Functional (Fair)</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Drive Philosophy (Transmission)</label>
                        <select name="transmission" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="AUTOMATIC">Precision Automatic</option>
                            <option value="MANUAL">Engaging Manual</option>
                            <option value="CVT">Seamless CVT</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Energy Source (Fuel Type)</label>
                        <select name="fuel_type" required class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold appearance-none outline-none">
                            <option value="GASOLINE">High-Octane Gasoline</option>
                            <option value="DIESEL">Industrial Diesel</option>
                            <option value="ELECTRIC">Pure Propulsion (EV)</option>
                            <option value="HYBRID">Hybrid Harmony</option>
                            <option value="PLUGIN_HYBRID">Plug-in Efficiency</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Asset Location</label>
                        <input type="text" name="location" value="Toronto, ON" placeholder="e.g. Toronto, ON"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Cinematic Walkaround (Video URL)</label>
                        <input type="url" name="walkaround_video_url" placeholder="YouTube or Vimeo link"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="flex items-center gap-4 pt-10 px-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="featured" class="sr-only peer">
                            <div class="w-14 h-7 bg-muted peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-accent shadow-sm"></div>
                            <span class="ms-3 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Showcase Highlight</span>
                        </label>
                    </div>
                </div>

                <div class="mt-10 space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Narrative (Description)</label>
                    <textarea name="description" rows="5" placeholder="Describe the soul and heritage of this vehicle..."
                              class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-3xl focus:ring-2 focus:ring-accent focus:border-accent transition font-medium italic outline-none"></textarea>
                </div>

                <div class="mt-8 space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Key Highlights (Comma Separated Features)</label>
                    <input type="text" name="features" placeholder="e.g. Leather Seats, Panoramic Sunroof, Adaptive Cruise Control"
                           class="w-full bg-background/50 border border-border text-foreground px-6 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>
        </div>

        <!-- Sidebar Content (Media) -->
        <div class="space-y-12">
            <div class="glass p-8 rounded-[3rem] border border-border/50 shadow-xl">
                <h3 class="text-lg font-black text-foreground tracking-tighter uppercase mb-6 flex items-center gap-3">
                    <i class="fas fa-camera text-accent"></i>
                    Cinematic Media
                </h3>
                
                <div id="drop-zone" class="border-2 border-dashed border-border/50 rounded-[2rem] p-8 text-center hover:border-accent hover:bg-accent/5 transition-all cursor-pointer group">
                    <input type="file" name="images[]" id="file-input" multiple accept="image/*" class="hidden">
                    <div class="w-16 h-16 bg-accent/10 rounded-2xl flex items-center justify-center text-accent mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-cloud-upload-alt text-2xl"></i>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-foreground mb-1">Upload Visuals</p>
                    <p class="text-[8px] font-bold text-muted-foreground uppercase tracking-widest px-4">Drag assets here or tap to browse</p>
                </div>

                <div id="preview-grid" class="grid grid-cols-2 gap-4 mt-8">
                    <!-- Previews will go here -->
                </div>
            </div>

            <!-- Action Controls -->
            <div class="sticky top-32 space-y-4">
                <button type="submit" class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Commit to Fleet</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
                <a href="../dashboard.php" class="block w-full text-center py-5 rounded-[2rem] border border-border text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:text-foreground hover:bg-muted transition-all">
                    Discard Entry
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
renderAdminLayout($content, 'Specification Editor');
?>