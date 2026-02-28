<?php
/**
 * pages/customer/profile.php
 * Member Identity Profile management
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/customer-layout.php';

requireAuth();
$user = getUserInfo();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        setFlash('error', 'Security integrity compromised. Unauthorized access attempt.');
        redirect(url('customer/profile'));
    }

    $name = clean($_POST['name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $address = clean($_POST['address'] ?? '');

    if (empty($name)) {
        setFlash('error', 'Identity signature cannot be null.');
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $phone, $address, $user['id']]);
        setFlash('success', 'Intelligence record updated successfully.');
        redirect(url('customer/profile'));
    }
}

$success = getFlash('success');
$error = getFlash('error');

ob_start();
?>

<div class="mb-12">
    <h1 class="text-4xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">Identity <span class="text-gradient">Profile.</span></h1>
    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Manage your authenticated member credentials</p>
</div>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Profile Edit Form -->
    <div class="lg:col-span-2">
        <div class="glass p-12 rounded-[4rem] border border-border/50 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-12 opacity-[0.02]">
                <i class="fas fa-shield-halved text-[12rem]"></i>
            </div>

            <form method="POST" class="space-y-10 relative z-10">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Identity Signature (Name)</label>
                        <input type="text" name="name" value="<?php echo clean($user['name']); ?>" required
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3 opacity-60 grayscale cursor-not-allowed">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Authenticated Index (Email)</label>
                        <input type="email" value="<?php echo clean($user['email']); ?>" disabled
                               class="w-full bg-muted border border-border text-muted-foreground px-6 py-5 rounded-2xl font-bold cursor-not-allowed">
                        <span class="text-[7px] font-black uppercase tracking-widest text-accent mt-1 block">Security Locked - Contact admin to change index</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Executive Line (Phone)</label>
                        <input type="text" name="phone" value="<?php echo clean($user['phone']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Deployment Address (Shipping)</label>
                        <input type="text" name="address" value="<?php echo clean($user['address']); ?>"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="inline-flex items-center gap-4 px-12 py-6 bg-accent text-white rounded-[2rem] font-black uppercase tracking-widest text-[11px] shadow-[0_15px_40px_rgba(249,115,22,0.3)] hover:scale-[1.03] active:scale-[0.98] transition-all group">
                        Confirm Intelligence Update
                        <i class="fas fa-fingerprint text-sm group-hover:rotate-12 transition-transform"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Information -->
    <div class="space-y-8">
        <section class="glass p-10 rounded-[3rem] border border-border shadow-xl bg-accent/[0.02] border-accent/20">
            <h3 class="text-xs font-black uppercase tracking-widest text-accent mb-6 flex items-center gap-2">
                <i class="fas fa-user-lock"></i>
                Security Protocol
            </h3>
            <p class="text-[11px] font-medium text-foreground/70 leading-relaxed mb-8 italic">Your identity record is encrypted and secured under executive protocol. Changes to your primary email index require manual verification by the administrative team.</p>
            <div class="space-y-4">
                <div class="flex items-center gap-4 p-4 rounded-2xl bg-background/80 border border-border">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <span class="block text-[8px] font-black uppercase text-muted-foreground">Access Key</span>
                        <a href="<?php echo url('change-password'); ?>" class="text-[10px] font-black text-foreground hover:text-accent transition-colors uppercase tracking-widest">Rotate Cipher</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="glass p-10 rounded-[3rem] border border-border shadow-xl">
             <h3 class="text-xs font-black uppercase tracking-widest text-muted-foreground mb-6">Record Insights</h3>
             <div class="space-y-6">
                 <div>
                     <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">Authenticated Since</span>
                     <p class="text-sm font-bold text-foreground"><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                 </div>
                 <div>
                     <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">Status Clearance</span>
                     <span class="px-2 py-0.5 rounded bg-accent/10 text-accent text-[9px] font-black uppercase tracking-widest border border-accent/20">Active Member</span>
                 </div>
             </div>
        </section>
    </div>
</div>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'Identity Profile');
?>
