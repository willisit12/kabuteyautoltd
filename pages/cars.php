<?php
/**
 * pages/cars.php
 * Inventory Listing Template
 */

$pageTitle = 'Inventory';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$filters = [
    'make' => clean($_GET['make'] ?? ''),
    'model' => clean($_GET['model'] ?? ''),
    'year' => clean($_GET['year'] ?? ''),
    'min_price' => clean($_GET['min_price'] ?? ''),
    'max_price' => clean($_GET['max_price'] ?? ''),
    'search' => clean($_GET['search'] ?? ''),
    'fuel_type' => clean($_GET['fuel_type'] ?? ''),
];

$cars = searchCars($filters, $limit, $offset);

// For total, we can count without limit
$db = getDB();
$totalSql = "SELECT COUNT(*) FROM cars c WHERE c.status = 'AVAILABLE'";
$totalParams = [];

if (!empty($filters['make'])) { $totalSql .= " AND c.make = ?"; $totalParams[] = $filters['make']; }
if (!empty($filters['model'])) { $totalSql .= " AND c.model LIKE ?"; $totalParams[] = '%' . $filters['model'] . '%'; }
if (!empty($filters['year'])) { $totalSql .= " AND c.year = ?"; $totalParams[] = $filters['year']; }
if (!empty($filters['min_price'])) { $totalSql .= " AND c.price >= ?"; $totalParams[] = $filters['min_price']; }
if (!empty($filters['max_price'])) { $totalSql .= " AND c.price <= ?"; $totalParams[] = $filters['max_price']; }

if (!empty($filters['search'])) {
    $totalSql .= " AND (c.model LIKE ? OR c.trim LIKE ? OR c.vin LIKE ?)";
    $st = '%' . $filters['search'] . '%';
    $totalParams[] = $st; $totalParams[] = $st; $totalParams[] = $st;
}

$stmt = $db->prepare($totalSql);
$stmt->execute($totalParams);
$totalCars = $stmt->fetchColumn();
$totalPages = ceil($totalCars / $limit);

$makes = getCarMakes();

include_once __DIR__ . '/../includes/layout/header.php';
?>

<section class="relative pt-32 pb-20 overflow-hidden bg-background transition-colors duration-500">
    <!-- Background Elements -->
    <div class="absolute inset-0 z-0 opacity-30 dark:opacity-20 pointer-events-none">
        <div class="absolute top-0 right-[-10%] w-[500px] h-[500px] bg-accent/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 left-[-10%] w-[400px] h-[400px] bg-accent/10 rounded-full blur-[100px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="mb-12 reveal-section">
            <span class="inline-block text-accent font-bold tracking-[0.2em] uppercase text-xs mb-4">
                The Williams Collection
            </span>
            <h1 class="text-5xl md:text-8xl font-black text-foreground mb-4 tracking-tighter uppercase leading-[0.9]">
                Professional <br> 
                <span class="text-gradient">Showroom</span>
            </h1>
            <p class="text-muted-foreground text-lg md:text-xl max-w-2xl leading-relaxed italic border-l-2 border-accent pl-6 mt-6">
                Explore Toronto’s most curated selection of pre-owned luxury and performance vehicles. Engineered for the discerning enthusiast.
            </p>
        </div>
        
        <!-- Sophisticated Filter System -->
        <div class="glass rounded-[2.5rem] p-8 md:p-10 mb-20 reveal-section shadow-2xl relative overflow-hidden group">
            <div class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-accent/30 to-transparent"></div>
            
            <form method="GET" action="<?php echo url('cars'); ?>" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <!-- Make Selection -->
                <div class="md:col-span-3 space-y-2.5">
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-foreground/40 ml-1">Manufacturer</label>
                    <div class="relative group">
                        <select name="make" class="w-full bg-background border border-border/60 text-foreground px-5 h-14 rounded-2xl focus:ring-2 focus:ring-accent/20 focus:border-accent transition appearance-none cursor-pointer font-bold shadow-sm group-hover:border-border">
                            <option value="">All Makes</option>
                            <?php foreach ($makes as $make): ?>
                                <option value="<?php echo $make; ?>" <?php echo ($filters['make'] === $make) ? 'selected' : ''; ?>><?php echo $make; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-accent pointer-events-none text-[10px]"></i>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="md:col-span-3 space-y-2.5">
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-foreground/40 ml-1"><?php echo __('search_placeholder'); ?></label>
                    <div class="relative group">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-muted-foreground/50 group-focus-within:text-accent transition-colors text-sm"></i>
                        <input type="text" name="search" value="<?php echo clean($filters['search']); ?>" 
                               placeholder="Model, Year, VIN..."
                               class="w-full bg-background border border-border/60 rounded-2xl h-14 pl-12 pr-5 focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all font-bold text-foreground placeholder:text-muted-foreground/30 shadow-sm group-hover:border-border">
                    </div>
                </div>

                <!-- Price Ceiling -->
                <div class="md:col-span-3 space-y-2.5">
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-foreground/40 ml-1">Investment Ceiling</label>
                    <div class="relative group">
                        <input type="number" name="max_price" value="<?php echo $filters['max_price']; ?>" placeholder="Maximum Budget" 
                               class="w-full bg-background border border-border/60 text-foreground px-5 h-14 rounded-2xl focus:ring-2 focus:ring-accent/20 focus:border-accent transition font-bold placeholder:text-muted-foreground/30 shadow-sm group-hover:border-border">
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-foreground/20 text-xs font-bold leading-none">$</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="md:col-span-3 flex gap-3 h-14">
                    <button type="submit" class="flex-[2] bg-accent text-white font-black rounded-2xl shadow-[0_10px_20px_-5px_rgba(249,115,22,0.4)] hover:shadow-[0_15px_25px_-5px_rgba(249,115,22,0.5)] hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-2 uppercase tracking-widest text-[10px]">
                        <i class="fas fa-filter text-[10px]"></i> <?php echo __('apply_filter'); ?>
                    </button>
                    <a href="<?php echo url('cars'); ?>" class="flex-1 bg-muted/40 text-muted-foreground font-black rounded-2xl hover:bg-muted transition-all flex items-center justify-center border border-border/50 group/clear">
                        <i class="fas fa-times text-[10px] group-hover:rotate-90 transition-transform duration-300"></i>
                    </a>
                </div>
            </form>
        </div>

        <?php if (count($cars) > 0): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="inventory-grid">
            <?php foreach ($cars as $car): ?>
                <?php renderCarCard($car, 'opacity-0 translate-y-10'); ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-24 reveal-section">
            <div class="glass flex items-center p-2 rounded-2xl border border-border shadow-lg">
                <?php if ($page > 1): ?>
                    <a href="<?php echo url('cars?page=' . ($page - 1) . '&' . http_build_query($filters)); ?>" class="w-12 h-12 flex items-center justify-center rounded-xl text-foreground hover:bg-accent hover:text-white transition-all">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                <?php endif; ?>

                <div class="flex px-2 space-x-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?php echo url('cars?page=' . $i . '&' . http_build_query($filters)); ?>" 
                       class="w-12 h-12 flex items-center justify-center rounded-xl font-black text-xs transition-all <?php echo $page == $i ? 'bg-accent text-white shadow-lg' : 'text-foreground hover:bg-muted'; ?>">
                        <?php echo sprintf("%02d", $i); ?>
                    </a>
                    <?php endfor; ?>
                </div>

                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo url('cars?page=' . ($page + 1) . '&' . http_build_query($filters)); ?>" class="w-12 h-12 flex items-center justify-center rounded-xl text-foreground hover:bg-accent hover:text-white transition-all">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-32 glass rounded-[3rem] border border-dashed border-border/60 reveal-section">
            <i class="fas fa-car-side text-8xl text-accent/20 mb-8"></i>
            <h3 class="text-3xl md:text-4xl font-black text-foreground mb-4 uppercase tracking-tighter">Zero Connection Found</h3>
            <p class="text-muted-foreground mt-2 max-w-sm mx-auto italic leading-relaxed">Our scouts are constantly sourcing. Try adjusting your filter parameters or contact our concierge for private sourcing.</p>
            <a href="<?php echo url('contact'); ?>" class="inline-block mt-8 text-accent font-bold border-b-2 border-accent pb-1 hover:text-foreground transition-colors uppercase tracking-widest text-xs">Contact Concierge</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Fallback: If GSAP fails or doesn't run, show cards after 1.5 seconds
        const fallback = setTimeout(() => {
            const items = document.querySelectorAll('.car-card, .reveal-section');
            items.forEach(c => {
                c.style.opacity = '1';
                c.style.transform = 'translateY(0)';
            });
        }, 1500);

        if (typeof gsap !== 'undefined') {
            const tl = gsap.timeline({ 
                defaults: { 
                    ease: "power3.out",
                    force3D: true 
                },
                onComplete: () => {
                    clearTimeout(fallback);
                    // Ensure full cleanup for performance
                    gsap.set(".car-card", { clearProps: "transform" });
                }
            });
            
            tl.from(".reveal-section", {
                y: 20,
                opacity: 0,
                duration: 0.8,
                stagger: 0.1
            })
            .to(".car-card", {
                y: 0,
                opacity: 1,
                duration: 0.7,
                stagger: 0.05,
                ease: "expo.out"
            }, "-=0.4");

            // Optimized Hover using GSAP (More efficient than pure CSS for complex transforms)
            gsap.utils.toArray('.car-card').forEach(card => {
                const hoverTl = gsap.to(card, { 
                    y: -8, 
                    scale: 1.01, 
                    duration: 0.4, 
                    ease: "power2.out", 
                    paused: true,
                    force3D: true
                });
                
                card.addEventListener('mouseenter', () => hoverTl.play());
                card.addEventListener('mouseleave', () => hoverTl.reverse());
            });
        }
    });
</script>
