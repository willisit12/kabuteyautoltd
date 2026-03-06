<?php
/**
 * admin/settings/markup.php - Global Markup Management
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Sequence aborted.';
    } else {
        $markupValue = (float)($_POST['markup_cny'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE global_settings SET `value` = ? WHERE `key` = 'markup_cny'");
            $stmt->execute([$markupValue]);
            $success = 'Global markup coefficient updated successfully.';
        } catch (Exception $e) {
            $error = 'Critical failure: ' . $e->getMessage();
        }
    }
}

$currentMarkup = getGlobalMarkup();

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="mb-12 text-center">
        <div class="w-16 h-16 bg-accent/10 border border-accent/20 rounded-2xl flex items-center justify-center text-accent mx-auto mb-6 shadow-lg">
            <i class="fas fa-coins text-2xl"></i>
        </div>
        <h3 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Pricing Intelligence</h3>
        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Adjust the global profit margin coefficient</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
            <i class="fas fa-check-circle text-xl"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold animate-pulse">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="glass rounded-[2.5rem] border border-border/50 p-10 bg-card/30 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <i class="fas fa-microchip text-8xl"></i>
        </div>
        
        <form method="POST" class="relative z-10 space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="space-y-4">
                <label class="block text-[11px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-2">Global Markup (CNY ¥)</label>
                <div class="relative group">
                    <div class="absolute left-6 top-1/2 -translate-y-1/2 text-accent font-black text-xl">¥</div>
                    <input type="number" step="0.01" name="markup_cny" value="<?php echo $currentMarkup; ?>" 
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-8 py-6 rounded-[1.5rem] focus:ring-4 focus:ring-accent/20 focus:border-accent transition-all font-black text-2xl outline-none shadow-inner"
                           placeholder="e.g. 5000">
                </div>
                <p class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest leading-relaxed ml-2 italic">
                    This amount will be automatically converted and added to every vehicle price across the entire platform.
                </p>
            </div>

            <button type="submit" class="w-full btn-premium bg-accent text-white py-6 rounded-[1.5rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.02] active:scale-95 transition-all shadow-[0_15px_30px_rgba(249,115,22,0.35)] flex items-center justify-center gap-3">
                <i class="fas fa-sync-alt animate-spin-slow"></i>
                Commit Logic Update
            </button>
        </form>
    </div>

    <!-- Impact Analysis -->
    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass p-6 rounded-3xl border border-border/50 text-center">
            <p class="text-[9px] font-black text-muted-foreground uppercase mb-2">USD Impact</p>
            <p class="text-xl font-black text-foreground">+$<?php echo number_format(I18n::convertBetween($currentMarkup, 'CNY', 'USD'), 0); ?></p>
        </div>
        <div class="glass p-6 rounded-3xl border border-border/50 text-center">
            <p class="text-[9px] font-black text-muted-foreground uppercase mb-2">EUR Impact</p>
            <p class="text-xl font-black text-foreground">+€<?php echo number_format(I18n::convertBetween($currentMarkup, 'CNY', 'EUR'), 0); ?></p>
        </div>
        <div class="glass p-6 rounded-3xl border border-border/50 text-center">
            <p class="text-[9px] font-black text-muted-foreground uppercase mb-2">GBP Impact</p>
            <p class="text-xl font-black text-foreground">+£<?php echo number_format(I18n::convertBetween($currentMarkup, 'CNY', 'GBP'), 0); ?></p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Pricing Intel');
?>
