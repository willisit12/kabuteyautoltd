<?php
/**
 * pages/contact.php
 * Contact Page Template
 */

$pageTitle = 'Contact Us';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
    } else {
        $name = clean($_POST['name'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $subject = clean($_POST['subject'] ?? 'General Inquiry');
        $message = clean($_POST['message'] ?? '');
        $carId = intval($_POST['car_id'] ?? 0);
        
        $db = getDB();
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

        try {
            $stmt = $db->prepare("INSERT INTO inquiries (user_id, car_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, 'PENDING')");
            $stmt->execute([$userId, $carId > 0 ? $carId : null, $name, $email, $subject, $message]);
            
            setFlash('success', 'Thank you for your inquiry! Our concierge team will reach out shortly.');
            redirect(url('contact' . ($carId ? '?id=' . $carId : '')));
        } catch (PDOException $e) {
            setFlash('error', 'We encountered a transmission failure: ' . $e->getMessage());
        }
    }
}

$success = getFlash('success');
$error = getFlash('error');
$carId = intval($_GET['id'] ?? 0);
$car = $carId ? getCarById($carId) : null;

include_once __DIR__ . '/../includes/layout/header.php';
?>

<section class="relative pt-32 pb-24 overflow-hidden bg-background transition-colors duration-500">
    <!-- Background Elements -->
    <div class="absolute inset-0 z-0 opacity-20 dark:opacity-10 pointer-events-none">
        <div class="absolute top-0 right-[-10%] w-[600px] h-[600px] bg-accent/15 rounded-full blur-[140px]"></div>
        <div class="absolute bottom-0 left-[-10%] w-[500px] h-[500px] bg-accent/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-start">
            <!-- Left Column: Contact Info -->
            <div class="reveal-section space-y-12">
                <div>
                    <span class="inline-block text-accent font-black tracking-[0.3em] uppercase text-[10px] mb-4">
                        Direct Concierge Access
                    </span>
                    <h1 class="text-5xl md:text-8xl font-black text-foreground mb-6 tracking-tighter uppercase leading-[0.9]">
                        Get In <br> 
                        <span class="text-gradient">Touch.</span>
                    </h1>
                    <p class="text-muted-foreground text-lg md:text-xl leading-relaxed italic border-l-2 border-accent/20 pl-6 max-w-lg">
                        Have a question about a specific masterpiece or want to book a private cinematic walkaround? Our elite concierge team is standing by.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-6">
                    <!-- Phone -->
                    <div class="glass p-6 rounded-[2rem] border border-border/50 flex items-center gap-6 group hover:border-accent transition-all duration-500">
                        <div class="w-14 h-14 bg-muted rounded-2xl flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground mb-1">Direct Line</h4>
                            <p class="text-xl font-black text-foreground">+233202493547</p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="glass p-6 rounded-[2rem] border border-border/50 flex items-center gap-6 group hover:border-accent transition-all duration-500">
                        <div class="w-14 h-14 bg-muted rounded-2xl flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground mb-1">Electronic Correspondence</h4>
                            <p class="text-xl font-black text-foreground">hello@williamsauto.com</p>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="glass p-6 rounded-[2rem] border border-border/50 flex items-center gap-6 group hover:border-accent transition-all duration-500">
                        <div class="w-14 h-14 bg-muted rounded-2xl flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground mb-1">The Showroom</h4>
                            <p class="text-xl font-black text-foreground">Toronto, ON, Canada</p>
                        </div>
                    </div>
                </div>

                <!-- Social Links -->
                <div class="flex gap-4">
                    <a href="#" class="w-12 h-12 glass rounded-xl flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all border border-border/50"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="w-12 h-12 glass rounded-xl flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all border border-border/50"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-12 h-12 glass rounded-xl flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all border border-border/50"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="w-12 h-12 glass rounded-xl flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all border border-border/50"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <!-- Right Column: Form -->
            <div class="reveal-section">
                <div class="glass p-10 md:p-12 rounded-[3.5rem] border border-border shadow-2xl relative overflow-hidden group">
                    <div class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>
                    
                    <h3 class="text-2xl md:text-3xl font-black text-foreground mb-8 tracking-tighter uppercase">Initiate <span class="text-accent">Inquiry.</span></h3>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold relative z-10">
                            <i class="fas fa-check-circle text-xl"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold relative z-10">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo url('contact'); ?>" class="space-y-8">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="car_id" value="<?php echo $carId; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-foreground/60 ml-1">Full Name</label>
                                <input type="text" name="name" required placeholder="Marcus Williams"
                                       class="w-full bg-background border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/30 shadow-sm">
                            </div>
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-foreground/60 ml-1">Email Address</label>
                                <input type="email" name="email" required placeholder="m.williams@collection.com"
                                       class="w-full bg-background border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/30 shadow-sm">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-foreground/60 ml-1">Inquiry Subject</label>
                            <input type="text" name="subject" value="<?php echo $car ? 'Selection Inquiry: ' . $car['year'] . ' ' . $car['make'] . ' ' . $car['model'] : ''; ?>" placeholder="General Inquiry"
                                   class="w-full bg-background border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/30 shadow-sm">
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-foreground/60 ml-1">Your Correspondence</label>
                            <textarea name="message" rows="5" required placeholder="I am interested in detailed documentation for the selection..."
                                      class="w-full bg-background border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold placeholder:text-muted-foreground/30 shadow-sm resize-none"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-accent text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_15px_40px_rgba(249,115,22,0.4)] relative group overflow-hidden">
                            <span class="relative z-10">Transmit Selection Inquiry</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                        </button>
                    </form>
                </div>

                <p class="text-center mt-12 text-muted-foreground text-xs font-bold italic opacity-60">
                    Expected response cycle within 24 standard hours.
                </p>
            </div>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tl = gsap.timeline({ defaults: { ease: "power4.out" }});
        
        tl.from(".reveal-section", {
            y: 50,
            opacity: 0,
            duration: 1.4,
            stagger: 0.3,
            clearProps: "all"
        });
    });
</script>
