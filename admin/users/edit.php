<?php
/**
 * admin/users/edit.php - Access Control (Edit User)
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

if (!isAdminRole()) {
    setFlash('error', 'You do not have permission to modify identities.');
    redirect('../dashboard.php');
}

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Identity signature not found.');
    redirect('index.php');
}

$success = getFlash('success');
$error = getFlash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
        redirect('edit.php?id=' . $id);
    }

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $role = clean($_POST['role'] ?? 'user');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email)) {
        setFlash('error', 'Required identification fields are incomplete.');
        redirect('edit.php?id=' . $id);
    }

    if (!validateEmail($email)) {
        setFlash('error', 'Email protocol mismatch.');
        redirect('edit.php?id=' . $id);
    }

    // Check email uniqueness
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        setFlash('error', 'Identity conflict: Email already claimed.');
        redirect('edit.php?id=' . $id);
    }

    try {
        if (!empty($password)) {
            if (strlen($password) < 8) {
                setFlash('error', 'Security key must be at least 8 characters.');
                redirect('edit.php?id=' . $id);
            }
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $password_hash, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $id]);
        }

        setFlash('success', 'Identity protocols updated successfully.');
        redirect('edit.php?id=' . $id);
    } catch (PDOException $e) {
        setFlash('error', 'Modification failure: ' . $e->getMessage());
        redirect('edit.php?id=' . $id);
    }
}

ob_start();
?>

<div class="max-w-2xl mx-auto py-12 px-4">
    <div class="glass p-12 rounded-[3.5rem] border border-border/50 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-5">
            <i class="fas fa-user-edit text-8xl"></i>
        </div>

        <div class="mb-12 relative z-10 flex items-center gap-6">
            <a href="index.php" class="w-12 h-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-white transition-all">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Modify Identity</h1>
                <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Adjust Account Parameters</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold relative z-10">
                <i class="fas fa-check-circle text-xl"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold animate-pulse relative z-10">
                <i class="fas fa-exclamation-triangle text-xl"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8 relative z-10">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Full Identity (Name)</label>
                <div class="relative group">
                    <i class="fas fa-id-card absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="name" required value="<?php echo clean($user['name']); ?>"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Communication Point (Email)</label>
                <div class="relative group">
                    <i class="fas fa-at absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                    <input type="email" name="email" required value="<?php echo clean($user['email']); ?>"
                           class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <div class="grid grid-cols-1 gap-8">
                <div class="space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Account Rank</label>
                    <select name="role" required class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Standard User</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator Elite</option>
                    </select>
                </div>
            </div>
            <?php else: ?>
                <input type="hidden" name="role" value="<?php echo clean($user['role']); ?>">
                <p class="text-[10px] font-bold text-accent uppercase tracking-widest px-4 py-2 bg-accent/10 border border-accent/20 rounded-xl inline-block">Cannot modify own rank.</p>
            <?php endif; ?>

            <div class="mt-8 pt-8 border-t border-border/50">
                <div class="space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1 flex items-center justify-between">
                        <span>New Encryption Key (Password)</span>
                        <span class="text-foreground/40 text-[8px]">Leave blank if unchanged</span>
                    </label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="password" placeholder="••••••••"
                               class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none placeholder:text-muted-foreground/30">
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Commit Revisions</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Identity Modification');
?>
