<?php
/**
 * pages/login.php - Secure Vault Admin Entrance
 */
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url('admin/dashboard'));
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        setFlash('error', 'Security integrity compromised. Please retry.');
        redirect(url('login'));
    }

    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Identification format invalid.');
        redirect(url('login'));
    }

    if (empty($password)) {
        setFlash('error', 'Access key required.');
        redirect(url('login'));
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        
        // Update last login
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        setFlash('success', 'Access Granted. Welcome back, ' . clean($user['name']));

        $default_redirect = url('admin/dashboard');

        $redirect = $_SESSION['redirect_after_login'] ?? $default_redirect;
        unset($_SESSION['redirect_after_login']);
        redirect($redirect);
    } else {
        setFlash('error', 'Authentication failed. Incorrect credentials.');
        redirect(url('login'));
    }
}

$error = getFlash('error');
$success = getFlash('success');

$pageTitle = 'Secure Vault Access';
include_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="relative min-h-[80vh] flex items-center justify-center p-6 pt-32 pb-20 overflow-hidden bg-background transition-colors duration-500">
    <!-- Background Accents -->
    <div class="absolute inset-0 z-0 opacity-20 dark:opacity-30 pointer-events-none">
        <div class="absolute top-[-10%] right-[-10%] w-[800px] h-[800px] bg-accent/10 rounded-full blur-[160px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[600px] h-[600px] bg-accent/5 rounded-full blur-[140px]"></div>
    </div>

    <div class="w-full max-w-lg relative z-10">
        <!-- Logo/Header Area inside content -->
        <div class="text-center mb-10 reveal-content">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-accent/10 rounded-3xl mb-6 shadow-[0_20px_50px_rgba(249,115,22,0.2)]">
                <i class="fas fa-shield-alt text-4xl text-accent"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-black tracking-tighter uppercase leading-none mb-2">
                Secure <span class="text-gradient">Vault.</span>
            </h1>
            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-muted-foreground opacity-60">
                Administrative Authentication Hub
            </p>
        </div>

        <!-- Login Card -->
        <div class="glass border border-border/50 p-10 md:p-12 rounded-[3.5rem] shadow-2xl relative overflow-hidden reveal-content" style="animation-delay: 0.1s">
            <div class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>
            
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-8 flex items-center gap-3 text-sm font-bold animate-pulse">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="space-y-3">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Access Point (Email)</label>
                    <div class="relative group">
                        <i class="fas fa-at absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                        <input type="email" name="email" required placeholder="admin@williamsauto.com"
                               class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center ml-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Encryption Key (Password)</label>
                    </div>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="password" required placeholder="••••••••••••"
                               class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Initiate Secure Login</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
            </form>
        </div>

        <div class="text-center mt-12 reveal-content" style="animation-delay: 0.2s">
            <a href="<?php echo url(); ?>" class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground hover:text-foreground transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Exit to Public Site
            </a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>
