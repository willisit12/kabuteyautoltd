<?php
/**
 * admin/add-user.php - Access Control (Add User)
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

// Only admins can add users
if (!isAdminRole()) {
    setFlash('error', 'You do not have permission to add users');
    redirect('../dashboard.php');
}

$success = getFlash('success');
$error = getFlash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
        redirect('add.php');
    }

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = clean($_POST['role'] ?? 'user');

    if (empty($name) || empty($email) || empty($password)) {
        setFlash('error', 'Required identification fields are incomplete.');
        redirect('add.php');
    }

    if (!validateEmail($email)) {
        setFlash('error', 'Email protocol mismatch.');
        redirect('add.php');
    }

    if ($password !== $confirm_password) {
        setFlash('error', 'Encryption key mismatch.');
        redirect('add.php');
    }

    if (strlen($password) < 8) {
        setFlash('error', 'Security key must be at least 8 characters.');
        redirect('add.php');
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'Identity already exists in the system.');
        redirect('add.php');
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    try {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password_hash, $role]);
        setFlash('success', 'New identity forged: ' . $email);
        redirect('add.php');
    } catch (PDOException $e) {
        setFlash('error', 'Forge failure: ' . $e->getMessage());
        redirect('add.php');
    }
}

ob_start();
?>

<div class="max-w-2xl mx-auto py-12 px-4">
    <div class="glass p-12 rounded-[3.5rem] border border-border/50 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-5">
            <i class="fas fa-user-shield text-8xl"></i>
        </div>

        <div class="mb-12">
            <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Access Control</h1>
            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Forge New Account Identity</p>
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
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Full Identity (Name)</label>
                <div class="relative group">
                    <i class="fas fa-id-card absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="name" required placeholder="Agent Smith"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Communication Point (Email)</label>
                <div class="relative group">
                    <i class="fas fa-at absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="email" name="email" required placeholder="agent@williamsauto.com"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Account Rank</label>
                    <select name="role" required class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                        <option value="user">Standard User</option>
                        <option value="admin">Administrator Elite</option>
                    </select>
                </div>
                <!-- Spacing for layout -->
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Encryption Key (Password)</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Verify Key</label>
                    <div class="relative group">
                        <i class="fas fa-shield-alt absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="confirm_password" required placeholder="••••••••"
                               class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Forge Identity</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
            </div>
        </form>

        <div class="mt-12 p-8 bg-muted/30 rounded-3xl border border-border/50">
            <h3 class="text-[10px] font-black text-foreground tracking-widest uppercase mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-accent"></i>
                Security Requirements
            </h3>
            <ul class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest space-y-3">
                <li class="flex items-center gap-3"><span class="w-1 h-1 bg-accent rounded-full"></span> Email must be unique to the Williams system</li>
                <li class="flex items-center gap-3"><span class="w-1 h-1 bg-accent rounded-full"></span> Password requires 8+ high-entropy characters</li>
                <li class="flex items-center gap-3"><span class="w-1 h-1 bg-accent rounded-full"></span> Admin Elite rank grants full system access</li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Access Control');
?>
