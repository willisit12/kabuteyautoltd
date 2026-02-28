<?php
/**
 * pages/customer/register.php - Customer Registration
 */
require_once __DIR__ . '/../../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(url('dashboard'));
}

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        setFlash('error', 'Security integrity compromised. Please retry.');
        redirect(url('register'));
    }

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        setFlash('error', 'All essential fields are required.');
        redirect(url('register'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Identification format invalid.');
        redirect(url('register'));
    }

    if ($password !== $confirm_password) {
        setFlash('error', 'Encryption keys do not match.');
        redirect(url('register'));
    }

    if (strlen($password) < 8) {
        setFlash('error', 'Encryption key must be at least 8 characters.');
        redirect(url('register'));
    }

    $db = getDB();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'This identification signature is already registered.');
        redirect(url('register'));
    }

    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
        $stmt->execute([$name, $email, $phone, $address, $hashed_password]);
        
        $user_id = $db->lastInsertId();
        loginUser($user_id);
        
        setFlash('success', 'Welcome to the Williams Auto Inner Circle, ' . $name);
        redirect(url('dashboard'));
    } catch (PDOException $e) {
        setFlash('error', 'Registration failure: ' . $e->getMessage());
        redirect(url('register'));
    }
}

$error = getFlash('error');
$success = getFlash('success');

$pageTitle = 'Join the Inner Circle';
include_once __DIR__ . '/../../includes/layout/header.php';
?>

<div class="relative min-h-[90vh] flex items-center justify-center p-6 pt-32 pb-20 overflow-hidden bg-background transition-colors duration-500">
    <!-- Background Accents -->
    <div class="absolute inset-0 z-0 opacity-20 dark:opacity-30 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[800px] h-[800px] bg-accent/10 rounded-full blur-[160px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[600px] h-[600px] bg-accent/5 rounded-full blur-[140px]"></div>
    </div>

    <div class="w-full max-w-2xl relative z-10">
        <!-- Logo/Header Area -->
        <div class="text-center mb-10 reveal-content">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-accent/10 rounded-3xl mb-6 shadow-[0_20px_50px_rgba(249,115,22,0.2)]">
                <i class="fas fa-user-plus text-4xl text-accent"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-black tracking-tighter uppercase leading-none mb-2">
                Inner <span class="text-gradient">Circle.</span>
            </h1>
            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-muted-foreground opacity-60">
                Customer Registry Enrollment
            </p>
        </div>

        <!-- Register Card -->
        <div class="glass border border-border/50 p-8 md:p-12 rounded-[3.5rem] shadow-2xl relative overflow-hidden reveal-content" style="animation-delay: 0.1s">
            <div class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>
            
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-8 flex items-center gap-3 text-sm font-bold animate-pulse">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Full Identity (Name)</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="name" required placeholder="Marcus Williams"
                                   class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Electronic Correspondence (Email)</label>
                        <div class="relative group">
                            <i class="fas fa-at absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                            <input type="email" name="email" required placeholder="m.williams@collection.com"
                                   class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Direct Line (Phone)</label>
                        <div class="relative group">
                            <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                            <input type="tel" name="phone" placeholder="+1 (416) 555-0199"
                                   class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Archive Address (Location)</label>
                        <div class="relative group">
                            <i class="fas fa-map-marker-alt absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="address" placeholder="Toronto, ON"
                                   class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Encryption Key (Password)</label>
                        <div class="relative group">
                            <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                            <input type="password" name="password" required placeholder="Min. 8 characters"
                                   class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Verify Key (Confirm)</label>
                        <div class="relative group">
                            <i class="fas fa-shield-check absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                            <input type="password" name="confirm_password" required placeholder="Repeat access key"
                                   class="w-full bg-background/50 border border-border text-foreground pl-14 pr-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/20 outline-none">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                    <span class="relative z-10">Enroll in Inner Circle</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-border/50 text-center">
                <p class="text-xs font-bold text-muted-foreground italic">
                    Already a member? <a href="<?php echo url('login'); ?>" class="text-accent hover:underline">Access Vault</a>
                </p>
            </div>
        </div>

        <div class="text-center mt-12 reveal-content" style="animation-delay: 0.2s">
            <a href="<?php echo url(); ?>" class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground hover:text-foreground transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Return to Gallery
            </a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../includes/layout/footer.php'; ?>
