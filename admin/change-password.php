<?php
/**
 * admin/change-password.php - Security Vault (Change Password)
 */
require_once __DIR__ . '/../includes/layout/admin-layout.php';

$success = getFlash('success');
$error = getFlash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
        redirect('change-password.php');
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        setFlash('error', 'Encryption fields are incomplete.');
        redirect('change-password.php');
    }

    if ($new_password !== $confirm_password) {
        setFlash('error', 'New encryption key mismatch.');
        redirect('change-password.php');
    }

    if (strlen($new_password) < 8) {
        setFlash('error', 'Security key must be at least 8 characters.');
        redirect('change-password.php');
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        setFlash('error', 'Current security key is invalid.');
        redirect('change-password.php');
    }

    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
        setFlash('success', 'Security key synchronized successfully.');
        redirect('change-password.php');
    } else {
        setFlash('error', 'Synchronization failure.');
        redirect('change-password.php');
    }
}

ob_start();
?>

<div class="max-w-2xl mx-auto py-12 px-4">
    <div class="glass p-12 rounded-[3.5rem] border border-border/50 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-5">
            <i class="fas fa-shield-alt text-8xl text-foreground"></i>
        </div>

        <div class="mb-12">
            <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Security Vault</h1>
            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Synchronize Encryption Keys</p>
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

        <form method="POST" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Current Encryption Key</label>
                <div class="relative group">
                    <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="password" name="current_password" required placeholder="••••••••"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <div class="h-px bg-border/50 my-10"></div>

            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">New Encryption Key</label>
                <div class="relative group">
                    <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="password" name="new_password" required placeholder="••••••••"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Confirm New Key</label>
                <div class="relative group">
                    <i class="fas fa-shield-check absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="password" name="confirm_password" required placeholder="••••••••"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
                <p class="text-[8px] font-bold text-muted-foreground uppercase tracking-widest ml-1 mt-2">Minimum 8 high-entropy characters required</p>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Synchronize Keys</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Security Vault');
?>
