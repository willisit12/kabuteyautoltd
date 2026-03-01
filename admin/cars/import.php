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
$error   = getFlash('error');
$results = getFlash('import_results');

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
            <i class="fas fa-check-circle text-xl flex-shrink-0"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
            <i class="fas fa-exclamation-triangle text-xl flex-shrink-0"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($results): ?>
        <?php $r = json_decode($results, true); ?>
        <div class="glass p-8 rounded-[2.5rem] border border-border/50 mb-10">
            <h3 class="text-sm font-black text-foreground uppercase tracking-widest mb-6 flex items-center gap-3">
                <i class="fas fa-chart-bar text-accent"></i> Import Report
            </h3>
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-green-500/10 border border-green-500/20 rounded-2xl p-4 text-center">
                    <p class="text-2xl font-black text-green-500"><?php echo $r['imported'] ?? 0; ?></p>
                    <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground mt-1">Imported</p>
                </div>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4 text-center">
                    <p class="text-2xl font-black text-blue-500"><?php echo $r['skipped'] ?? 0; ?></p>
                    <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground mt-1">Skipped</p>
                </div>
                <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 text-center">
                    <p class="text-2xl font-black text-red-500"><?php echo count($r['errors'] ?? []); ?></p>
                    <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground mt-1">Errors</p>
                </div>
            </div>
            <?php if (!empty($r['errors'])): ?>
                <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-red-500 mb-3">Error Details</p>
                    <ul class="space-y-1">
                        <?php foreach ($r['errors'] as $err): ?>
                            <li class="text-[10px] font-bold text-muted-foreground flex items-start gap-2">
                                <i class="fas fa-times-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                                <?php echo htmlspecialchars($err); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!empty($r['log'])): ?>
                <details class="mt-4">
                    <summary class="text-[9px] font-black uppercase tracking-widest text-muted-foreground cursor-pointer hover:text-foreground transition-colors">Show full log (<?php echo count($r['log']); ?> entries)</summary>
                    <div class="mt-3 bg-muted/30 rounded-2xl p-4 max-h-48 overflow-y-auto custom-scrollbar">
                        <?php foreach ($r['log'] as $entry): ?>
                            <p class="text-[10px] font-mono text-muted-foreground leading-relaxed"><?php echo htmlspecialchars($entry); ?></p>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-muted/30 p-6 rounded-2xl border border-border/50">
                <div class="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center text-accent mb-4">
                    <i class="fas fa-spider text-xl"></i>
                </div>
                <h3 class="text-xs font-black text-foreground uppercase tracking-widest mb-2">Step 1 — Scrape</h3>
                <p class="text-[10px] font-bold text-muted-foreground leading-relaxed">Run the scraper. Each car produces a folder with <code class="text-accent">data.json</code> and an <code class="text-accent">images/</code> subfolder.</p>
            </div>
            <div class="bg-muted/30 p-6 rounded-2xl border border-border/50">
                <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 mb-4">
                    <i class="fas fa-file-archive text-xl"></i>
                </div>
                <h3 class="text-xs font-black text-foreground uppercase tracking-widest mb-2">Step 2 — Zip</h3>
                <p class="text-[10px] font-bold text-muted-foreground leading-relaxed">Compress all car folders into a single <code class="text-accent">cars.zip</code>. The ZIP root must contain the car folders directly.</p>
            </div>
            <div class="bg-muted/30 p-6 rounded-2xl border border-border/50">
                <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center text-green-500 mb-4">
                    <i class="fas fa-upload text-xl"></i>
                </div>
                <h3 class="text-xs font-black text-foreground uppercase tracking-widest mb-2">Step 3 — Upload</h3>
                <p class="text-[10px] font-bold text-muted-foreground leading-relaxed">Upload the ZIP below. The system will parse each <code class="text-accent">data.json</code>, import images, and skip duplicates automatically.</p>
            </div>
        </div>

        <!-- ZIP structure diagram -->
        <div class="bg-muted/20 border border-border/50 rounded-2xl p-5 font-mono text-[10px] text-muted-foreground leading-relaxed">
            <p class="text-accent font-black mb-2">Expected ZIP structure:</p>
            <p>cars.zip</p>
            <p class="ml-4">├── audi-q3-2022/</p>
            <p class="ml-8">│   ├── data.json</p>
            <p class="ml-8">│   └── images/</p>
            <p class="ml-12">│       ├── 01.jpg</p>
            <p class="ml-12">│       └── 02.jpg ...</p>
            <p class="ml-4">├── bmw-x3-2023/</p>
            <p class="ml-8">│   ├── data.json</p>
            <p class="ml-8">│   └── images/ ...</p>
        </div>
    </div>

    <!-- Upload Form -->
    <form method="POST" action="import-process.php" enctype="multipart/form-data"
          class="glass p-10 rounded-[3rem] border border-border/50 shadow-xl relative overflow-hidden"
          id="import-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <h2 class="text-lg font-black text-foreground tracking-tighter uppercase mb-8 flex items-center gap-3">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Upload Station
        </h2>

        <!-- ZIP Upload -->
        <div class="space-y-3 mb-10">
            <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">
                <i class="fas fa-file-archive text-accent mr-2"></i>Scraped Cars Archive (.zip)
            </label>
            <div class="relative">
                <div id="zip-drop" class="border-2 border-dashed border-border rounded-2xl p-16 text-center cursor-pointer hover:border-accent hover:bg-accent/5 transition-all group">
                    <i class="fas fa-cloud-upload-alt text-4xl text-muted-foreground/30 group-hover:text-accent transition-colors mb-4 block"></i>
                    <p id="zip-label" class="text-sm font-bold text-muted-foreground group-hover:text-foreground transition-colors">
                        Drag & drop <span class="text-accent">cars.zip</span> here or <span class="text-accent underline">browse</span>
                    </p>
                    <p class="text-[9px] font-bold text-muted-foreground/50 mt-2 uppercase tracking-widest">Accepts .zip — max <?php echo ini_get('upload_max_filesize'); ?></p>
                </div>
                <input type="file" name="zip_file" id="zip-input" accept=".zip" required
                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
            </div>
            <!-- File size preview -->
            <p id="file-info" class="text-[10px] font-bold text-muted-foreground ml-1 hidden"></p>
        </div>

        <button type="submit" id="submit-btn"
                class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
            <span class="relative z-10 flex items-center justify-center gap-3" id="btn-label">
                <i class="fas fa-rocket"></i>
                Initiate Bulk Import
            </span>
        </button>

        <!-- Progress indicator (shown after submit) -->
        <div id="progress-wrap" class="hidden mt-8">
            <div class="flex items-center gap-4 mb-3">
                <i class="fas fa-spinner fa-spin text-accent text-xl"></i>
                <p class="text-sm font-black text-foreground">Processing import — do not close this page...</p>
            </div>
            <div class="w-full bg-muted rounded-full h-2">
                <div class="bg-accent h-2 rounded-full animate-pulse" style="width: 100%"></div>
            </div>
        </div>
    </form>
</div>

<script>
    const zipInput  = document.getElementById('zip-input');
    const zipLabel  = document.getElementById('zip-label');
    const zipDrop   = document.getElementById('zip-drop');
    const fileInfo  = document.getElementById('file-info');
    const submitBtn = document.getElementById('submit-btn');
    const btnLabel  = document.getElementById('btn-label');
    const progressWrap = document.getElementById('progress-wrap');

    zipInput.addEventListener('change', () => {
        if (zipInput.files.length > 0) {
            const file = zipInput.files[0];
            const mb   = (file.size / 1024 / 1024).toFixed(1);
            zipLabel.innerHTML = `<span class="text-accent font-black">${file.name}</span> selected`;
            zipDrop.classList.add('border-accent', 'bg-accent/5');
            zipDrop.classList.remove('border-border');
            fileInfo.textContent = `File size: ${mb} MB`;
            fileInfo.classList.remove('hidden');
        }
    });

    // Drag & drop
    zipDrop.addEventListener('dragover',  e => { e.preventDefault(); zipDrop.classList.add('border-accent','bg-accent/5'); });
    zipDrop.addEventListener('dragleave', () => { if (!zipInput.files.length) zipDrop.classList.remove('border-accent','bg-accent/5'); });
    zipDrop.addEventListener('drop', e => {
        e.preventDefault();
        zipInput.files = e.dataTransfer.files;
        zipInput.dispatchEvent(new Event('change'));
    });

    document.getElementById('import-form').addEventListener('submit', function() {
        btnLabel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-wait');
        progressWrap.classList.remove('hidden');
    });
</script>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Bulk Import');
?>
