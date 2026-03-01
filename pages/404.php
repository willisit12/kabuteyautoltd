<?php
/**
 * pages/404.php
 * Premium 404 Error Page — Page Not Found
 */

$pageTitle = 'Page Not Found';
include_once __DIR__ . '/../includes/layout/header.php';

// Fetch a few featured cars to show
$featuredCars = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT c.*, 
        m.name as make, 
        (SELECT url FROM car_images WHERE car_id = c.id ORDER BY `order` ASC LIMIT 1) as primary_image
        FROM cars c
        LEFT JOIN makes m ON c.make_id = m.id
        WHERE c.status = 'AVAILABLE'
        ORDER BY RAND()
        LIMIT 4");
    $featuredCars = $stmt->fetchAll();
} catch (Exception $e) {
    // Silently fail — we'll just show fewer cars
}
?>

<section class="relative min-h-screen flex flex-col items-center justify-center pt-24 pb-20 overflow-hidden bg-background">
    <!-- Ambient Background -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-accent/10 rounded-full blur-[160px] animate-pulse-slow"></div>
        <div class="absolute bottom-[-15%] right-[-10%] w-[500px] h-[500px] bg-accent/5 rounded-full blur-[130px] animate-pulse-slow" style="animation-delay: 1s;"></div>
        <!-- Road lines -->
        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-accent/20 to-transparent"></div>
        <div class="absolute bottom-4 left-0 right-0 h-px bg-gradient-to-r from-transparent via-border/30 to-transparent"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 w-full">
        <!-- Hero Error Block -->
        <div class="text-center mb-16 global-reveal">
            <!-- Animated 404 -->
            <div class="relative inline-block mb-8">
                <span class="text-[12rem] md:text-[16rem] font-black text-transparent leading-none tracking-tighter select-none" 
                      style="
                        -webkit-text-stroke: 2px rgba(var(--foreground-rgb), 0.06);
                        font-family: 'Outfit', sans-serif;
                      ">404</span>
                <!-- Accent overlay text -->
                <span class="absolute inset-0 flex items-center justify-center text-[12rem] md:text-[16rem] font-black leading-none tracking-tighter select-none"
                      style="
                        background: linear-gradient(135deg, rgba(249,115,22,0.15) 0%, transparent 60%);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        font-family: 'Outfit', sans-serif;
                      ">404</span>
                <!-- Floating icon -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-20 h-20 md:w-24 md:h-24 rounded-full bg-accent/10 border border-accent/20 flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-road text-accent text-2xl md:text-3xl"></i>
                </div>
            </div>

            <!-- Heading -->
            <div class="flex items-center justify-center gap-4 mb-4">
                <span class="w-12 h-[2px] bg-accent"></span>
                <span class="text-accent font-black uppercase tracking-[0.3em] text-[10px]">Wrong Turn</span>
                <span class="w-12 h-[2px] bg-accent"></span>
            </div>
            <h1 class="text-3xl md:text-5xl font-black text-foreground tracking-tighter uppercase mb-4" style="font-family: 'Outfit', sans-serif;">
                Road <span class="text-gradient">Not Found</span>
            </h1>
            <p class="text-muted-foreground text-base md:text-lg max-w-lg mx-auto font-medium leading-relaxed mb-10">
                Looks like this route hit a dead end. The page you're looking for may have been moved, renamed, or is taking a detour.
            </p>

            <!-- Quick Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
                <a href="<?php echo url(); ?>" 
                   class="btn-premium group bg-accent text-white px-8 py-4 rounded-2xl font-black uppercase tracking-tighter text-sm shadow-[0_10px_30px_rgba(249,115,22,0.3)] hover:scale-[1.02] active:scale-95 transition-all inline-flex items-center gap-3">
                    <i class="fas fa-home text-sm group-hover:scale-110 transition-transform"></i>
                    Back to Showroom
                </a>
                <a href="<?php echo url('cars'); ?>" 
                   class="btn-premium group bg-foreground text-background px-8 py-4 rounded-2xl font-black uppercase tracking-tighter text-sm hover:bg-accent hover:text-white transition-all inline-flex items-center gap-3 border border-transparent">
                    <i class="fas fa-car text-sm group-hover:scale-110 transition-transform"></i>
                    Browse Inventory
                </a>
            </div>

            <!-- Quick Navigation Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-2xl mx-auto">
                <a href="<?php echo url(); ?>" class="group glass rounded-2xl p-4 flex flex-col items-center gap-2 hover:border-accent/30 transition-all hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                        <i class="fas fa-home text-sm"></i>
                    </div>
                    <span class="text-xs font-bold text-muted-foreground group-hover:text-foreground transition-colors uppercase tracking-wider">Home</span>
                </a>
                <a href="<?php echo url('cars'); ?>" class="group glass rounded-2xl p-4 flex flex-col items-center gap-2 hover:border-accent/30 transition-all hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                        <i class="fas fa-car text-sm"></i>
                    </div>
                    <span class="text-xs font-bold text-muted-foreground group-hover:text-foreground transition-colors uppercase tracking-wider">Inventory</span>
                </a>
                <a href="<?php echo url('about'); ?>" class="group glass rounded-2xl p-4 flex flex-col items-center gap-2 hover:border-accent/30 transition-all hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                        <i class="fas fa-building text-sm"></i>
                    </div>
                    <span class="text-xs font-bold text-muted-foreground group-hover:text-foreground transition-colors uppercase tracking-wider">About</span>
                </a>
                <a href="<?php echo url('contact'); ?>" class="group glass rounded-2xl p-4 flex flex-col items-center gap-2 hover:border-accent/30 transition-all hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all">
                        <i class="fas fa-envelope text-sm"></i>
                    </div>
                    <span class="text-xs font-bold text-muted-foreground group-hover:text-foreground transition-colors uppercase tracking-wider">Contact</span>
                </a>
            </div>
        </div>

        <?php if (!empty($featuredCars)): ?>
        <!-- Featured Vehicles Section -->
        <div class="global-reveal border-t border-border/20 pt-16">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <span class="inline-block text-accent font-black tracking-[0.2em] uppercase text-[10px] mb-3">
                        While You're Here
                    </span>
                    <h2 class="text-2xl md:text-4xl font-black text-foreground uppercase tracking-tighter" style="font-family: 'Outfit', sans-serif;">
                        Explore Our <span class="text-gradient">Collection</span>
                    </h2>
                </div>
                <a href="<?php echo url('cars'); ?>" class="hidden sm:inline-flex text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-accent transition-colors items-center gap-2 group">
                    View All <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($featuredCars as $car): 
                    $image = $car['primary_image'] ? url($car['primary_image']) : 'https://placehold.co/400x300/1a1a2e/f97316?text=No+Image';
                    $slug = $car['slug'] ?? '';
                ?>
                <a href="<?php echo url('car-detail/' . $slug); ?>" 
                   class="group bg-white dark:bg-slate-800/40 rounded-[2rem] overflow-hidden border border-gray-100 dark:border-white/10 hover:border-accent/50 dark:hover:border-accent/50 transition-all duration-500 hover:-translate-y-2 shadow-sm hover:shadow-[0_20px_40px_rgba(0,0,0,0.1)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
                    <!-- Image -->
                    <div class="relative h-44 overflow-hidden">
                        <img src="<?php echo $image; ?>" 
                             alt="<?php echo clean(($car['make'] ?? '') . ' ' . ($car['model'] ?? '')); ?>"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-80"></div>
                        <!-- Year badge -->
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1.5 rounded-xl bg-accent text-white text-[10px] font-black uppercase tracking-widest shadow-lg shadow-accent/20">
                                <?php echo $car['year'] ?? ''; ?>
                            </span>
                        </div>
                    </div>
                    <!-- Info -->
                    <div class="p-6">
                        <h3 class="font-black text-foreground dark:text-white text-base tracking-tight group-hover:text-accent transition-colors mb-2 truncate uppercase">
                            <?php echo clean(($car['make'] ?? '') . ' ' . ($car['model'] ?? '')); ?>
                        </h3>
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-accent font-black text-lg tracking-tighter leading-none">
                                    <?php echo isset($car['price']) ? formatPrice($car['price'], $car['price_unit'] ?? null) : ''; ?>
                                </span>
                            </div>
                            <span class="px-3 py-1.5 rounded-lg bg-muted dark:bg-white/5 text-muted-foreground dark:text-gray-400 text-[9px] font-black uppercase tracking-widest border border-border dark:border-white/5">
                                <?php echo isset($car['mileage']) ? formatMileage($car['mileage']) : ''; ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 text-center sm:hidden">
                <a href="<?php echo url('cars'); ?>" class="inline-flex btn-premium bg-muted hover:bg-accent text-foreground hover:text-white px-6 py-3 rounded-2xl font-black uppercase tracking-tighter text-xs transition-all">
                    View All Vehicles
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>


<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>
