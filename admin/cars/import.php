<?php
/**
 * admin/cars/import.php - Bulk Vehicle Import Console
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

if (!isAdminRole()) {
    setFlash('error', 'Insufficient clearance for bulk operations.');
    redirect('../dashboard.php');
}

$success = getFlash('success');
$error = getFlash('error');

ob_start();
?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <!-- Back Link -->
    <div class="mb-12 flex items-center gap-6">
        <a href="index.php" class="w-12 h-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-white transition-all shadow-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Bulk Import</h1>
            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Mass Vehicle Data Ingestion Console</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
            <i class="fas fa-check-circle text-xl"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Instructions Card -->
    <div class="glass p-10 rounded-[3rem] border border-border/50 shadow-xl mb-10 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-10 opacity-5">
            <i class="fas fa-file-import text-8xl text-foreground"></i>
        </div>

        <h2 class="text-lg font-black text-foreground tracking-tighter uppercase mb-6 flex items-center gap-3">
            <span class="w-2 h-2 bg-accent rounded-full"></span>
            Import Protocol
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div class="bg-muted/30 p-6 rounded-2xl border border-border/50">
                <div class="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center text-accent mb-4">
                    <i class="fas fa-file-csv text-xl"></i>
                </div>
                <h3 class="text-xs font-black text-foreground uppercase tracking-widest mb-2">Step 1</h3>
                <p class="text-[10px] font-bold text-muted-foreground leading-relaxed">Download the CSV template and fill in vehicle details. Each row = one car.</p>
            </div>
            <div class="bg-muted/30 p-6 rounded-2xl border border-border/50">
                <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 mb-4">
                    <i class="fas fa-images text-xl"></i>
                </div>
                <h3 class="text-xs font-black text-foreground uppercase tracking-widest mb-2">Step 2</h3>
                <p class="text-[10px] font-bold text-muted-foreground leading-relaxed">Organize images in folders matching the <code class="text-accent">image_folder</code> column in CSV, then zip them.</p>
            </div>
            <div class="bg-muted/30 p-6 rounded-2xl border border-border/50">
                <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center text-green-500 mb-4">
                    <i class="fas fa-upload text-xl"></i>
                </div>
                <h3 class="text-xs font-black text-foreground uppercase tracking-widest mb-2">Step 3</h3>
                <p class="text-[10px] font-bold text-muted-foreground leading-relaxed">Upload both files below. The system will automatically process and catalog everything.</p>
            </div>
        </div>

        <a href="<?php echo url('config/car-import-template.csv'); ?>" download
           class="inline-flex items-center gap-3 px-6 py-3 bg-accent/10 text-accent rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-accent hover:text-white transition-all border border-accent/20">
            <i class="fas fa-download"></i>
            Download CSV Template
        </a>
    </div>

    <!-- Upload Form -->
    <form method="POST" action="import-process.php" enctype="multipart/form-data" class="glass p-10 rounded-[3rem] border border-border/50 shadow-xl relative overflow-hidden">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <h2 class="text-lg font-black text-foreground tracking-tighter uppercase mb-8 flex items-center gap-3">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Upload Station
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <!-- CSV Upload -->
            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                    <i class="fas fa-file-csv text-accent mr-2"></i>Vehicle Data File (.csv)
                </label>
                <div class="relative">
                    <div id="csv-drop" class="border-2 border-dashed border-border rounded-2xl p-10 text-center cursor-pointer hover:border-accent hover:bg-accent/5 transition-all group">
                        <i class="fas fa-cloud-upload-alt text-3xl text-muted-foreground/30 group-hover:text-accent transition-colors mb-4 block"></i>
                        <p id="csv-label" class="text-xs font-bold text-muted-foreground group-hover:text-foreground transition-colors">
                            Drag & drop CSV here or <span class="text-accent underline">browse</span>
                        </p>
                        <p class="text-[9px] font-bold text-muted-foreground/50 mt-2 uppercase tracking-widest">Accepts .csv files only</p>
                    </div>
                    <input type="file" name="csv_file" id="csv-input" accept=".csv" required class="absolute inset-0 opacity-0 cursor-pointer">
                </div>
            </div>

            <!-- ZIP Upload -->
            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                    <i class="fas fa-file-archive text-blue-500 mr-2"></i>Image Archive (.zip) <span class="text-muted-foreground/50">— optional</span>
                </label>
                <div class="relative">
                    <div id="zip-drop" class="border-2 border-dashed border-border rounded-2xl p-10 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-500/5 transition-all group">
                        <i class="fas fa-images text-3xl text-muted-foreground/30 group-hover:text-blue-500 transition-colors mb-4 block"></i>
                        <p id="zip-label" class="text-xs font-bold text-muted-foreground group-hover:text-foreground transition-colors">
                            Drag & drop ZIP here or <span class="text-blue-500 underline">browse</span>
                        </p>
                        <p class="text-[9px] font-bold text-muted-foreground/50 mt-2 uppercase tracking-widest">Accepts .zip up to 200MB</p>
                    </div>
                    <input type="file" name="zip_file" id="zip-input" accept=".zip" class="absolute inset-0 opacity-0 cursor-pointer">
                </div>
            </div>
        </div>

        <button type="submit" id="submit-btn"
                class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
            <span class="relative z-10 flex items-center justify-center gap-3">
                <i class="fas fa-rocket"></i>
                Initiate Bulk Import
            </span>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
        </button>
    </form>

    <!-- CSV Spec Reference -->
    <div class="mt-10 glass p-8 rounded-[2.5rem] border border-border/50">
        <h3 class="text-[10px] font-black text-foreground tracking-widest uppercase mb-6 flex items-center gap-2">
            <i class="fas fa-info-circle text-accent"></i>
            CSV Column Reference
        </h3>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-border/50">
                        <th class="py-3 px-4 text-[9px] font-black uppercase tracking-widest text-accent">Column</th>
                        <th class="py-3 px-4 text-[9px] font-black uppercase tracking-widest text-muted-foreground">Required</th>
                        <th class="py-3 px-4 text-[9px] font-black uppercase tracking-widest text-muted-foreground">Accepted Values</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/20 text-muted-foreground font-bold">
                    <tr><td class="py-3 px-4 text-foreground">make</td><td class="py-3 px-4 text-green-500">Yes</td><td class="py-3 px-4">e.g. Honda, Toyota, Ford</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">model</td><td class="py-3 px-4 text-green-500">Yes</td><td class="py-3 px-4">e.g. Civic, Camry, F-150</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">year</td><td class="py-3 px-4 text-green-500">Yes</td><td class="py-3 px-4">4-digit year (2020, 2024)</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">price</td><td class="py-3 px-4 text-green-500">Yes</td><td class="py-3 px-4">Numeric, no $ (24500)</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">mileage</td><td class="py-3 px-4 text-green-500">Yes</td><td class="py-3 px-4">Numeric (15000)</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">vin</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">17-character VIN</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">color</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">Any color name</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">fuel_type</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">GASOLINE, DIESEL, ELECTRIC, HYBRID, PLUGIN_HYBRID</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">transmission</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">AUTOMATIC, MANUAL, CVT</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">body_type</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">SEDAN, SUV, TRUCK, COUPE, HATCHBACK, VAN, CONVERTIBLE, WAGON</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">condition</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">EXCELLENT, VERY_GOOD, GOOD, FAIR</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">description</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">Free text</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">features</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">Comma-separated list</td></tr>
                    <tr><td class="py-3 px-4 text-foreground">image_folder</td><td class="py-3 px-4 text-amber-500">Optional</td><td class="py-3 px-4">Folder name inside ZIP matching this car's images</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Drag & Drop file label updates
    function setupDrop(inputId, labelId, dropId) {
        const input = document.getElementById(inputId);
        const label = document.getElementById(labelId);
        const drop = document.getElementById(dropId);

        input.addEventListener('change', () => {
            if (input.files.length > 0) {
                label.textContent = input.files[0].name;
                drop.classList.add('border-accent', 'bg-accent/5');
                drop.classList.remove('border-border');
            }
        });
    }
    setupDrop('csv-input', 'csv-label', 'csv-drop');
    setupDrop('zip-input', 'zip-label', 'zip-drop');

    // Show loading state on submit
    document.querySelector('form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        btn.innerHTML = '<span class="relative z-10 flex items-center justify-center gap-3"><i class="fas fa-spinner fa-spin"></i> Processing Import...</span>';
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-wait');
    });
</script>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Bulk Import');
?>
