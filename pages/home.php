<?php
/**
 * pages/home.php
 * Modern Homepage Template
 */

$pageTitle = 'The Williams Standard';
$featuredCars = getFeaturedCars(10);

// If we have few featured cars, double the array so the swiper loop looks seamless
if (count($featuredCars) > 0 && count($featuredCars) < 6) {
    $featuredCars = array_merge($featuredCars, $featuredCars);
}

// Initial cars for the grid
$initialLimit = 8;
$latestCars = searchCars([], $initialLimit);
$favoriteIds = isLoggedIn() ? getUserFavoriteIds($_SESSION['user_id'] ?? null) : [];

foreach ($latestCars as &$car) {
    $car['is_favorited'] = in_array($car['id'], $favoriteIds);
}
foreach ($featuredCars as &$car) {
    $car['is_favorited'] = in_array($car['id'], $favoriteIds);
}

$makes = getCarMakes();

// Fetch approved testimonials
$db = getDB();
$testimonialStmt = $db->prepare("SELECT t.*, c.make, c.model, c.year FROM testimonials t LEFT JOIN cars c ON t.car_id = c.id WHERE t.approved = 1 ORDER BY t.created_at DESC LIMIT 10");
$testimonialStmt->execute();
$testimonials = $testimonialStmt->fetchAll();

// If we have few testimonials, double the array so the swiper loop looks seamless
if (count($testimonials) > 0 && count($testimonials) < 6) {
    $testimonials = array_merge($testimonials, $testimonials);
}

include_once __DIR__ . '/../includes/layout/header.php';
?>

<!-- Cinematic Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20">
    <!-- Background Elements -->
    <div class="absolute inset-0 z-0 transition-colors duration-700">
        <!-- Theme-aware Overlay Gradient -->
        <div class="absolute inset-0 bg-gradient-to-b from-background/40 via-background/60 to-background dark:from-background/60 dark:via-background/80 dark:to-background z-10 transition-colors duration-700"></div>
        
        <!-- Background Image with Theme Tuning -->
        <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=1920" 
             alt="Luxury Car" 
             class="w-full h-full object-cover scale-110 grayscale-[0.2] dark:grayscale-0 brightness-110 dark:brightness-100 transition-all duration-700" 
             id="hero-bg">
             
        <!-- Bottom Accent Glow -->
        <div class="absolute bottom-0 left-0 w-full h-64 bg-gradient-to-t from-background to-transparent z-10"></div>
    </div>

    <!-- Hero Content -->
    <div class="relative z-20 max-w-6xl mx-auto px-4 text-center">
        <div class="hero-text-reveal overflow-hidden mb-4">
            <span class="inline-block text-accent font-bold tracking-[0.3em] uppercase text-sm animate-pulse-slow">
                EST. 2012 • TORONTO, CANADA
            </span>
        </div>
        
        <h1 class="text-4xl md:text-9xl font-black mb-6 md:mb-8 leading-[0.9] tracking-tighter text-foreground" id="main-title">
            DRIVING <br> 
            <span class="text-gradient">EXCELLENCE.</span>
        </h1>
        
        <p class="text-lg md:text-2xl text-muted-foreground max-w-2xl mx-auto mb-8 md:mb-12 leading-relaxed opacity-0" id="hero-desc">
            Experience the GTA's most curated collection of premium pre-owned vehicles. Cinematic service, unbeatable heritage.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 md:gap-6 justify-center items-center opacity-0" id="hero-btns">
            <a href="#inventory" class="w-full sm:w-auto btn-premium bg-accent text-white px-8 md:px-10 py-4 md:py-5 rounded-2xl font-bold text-base md:text-lg shadow-[0_10px_40px_rgba(249,115,22,0.3)] hover:scale-105 transition-transform active:scale-95 text-center">
                Explore Inventory
            </a>
            <a href="<?php echo url('about'); ?>" class="group flex items-center gap-4 text-foreground hover:text-accent transition-colors">
                <span class="w-10 h-10 md:w-12 md:h-12 rounded-full border border-border flex items-center justify-center group-hover:border-accent group-hover:bg-accent/10">
                    <i class="fas fa-play text-[10px] md:text-xs"></i>
                </span>
                Our Philosophy
            </a>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-20 opacity-0" id="scroll-hint">
        <div class="lg:hidden w-[30px] h-[50px] rounded-full border-2 border-border flex justify-center p-2">
            <div class="w-1.5 h-1.5 bg-accent rounded-full animate-bounce"></div>
        </div>
    </div>
</section>

<!-- Trust Metrics -->
<section class="py-10 relative z-20 bg-background shadow-[0_-50px_100px_rgba(0,0,0,0.05)] dark:shadow-[0_-50px_100px_rgba(0,0,0,0.5)] transition-colors duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
            <div class="car-metric-card p-6 md:p-10 rounded-[2rem] md:rounded-[3rem] bg-card border border-border hover:bg-card/80 transition-all duration-500">
                <div class="text-4xl md:text-5xl font-black text-foreground mb-2">150+</div>
                <div class="text-accent text-xs md:text-sm font-bold uppercase tracking-widest mb-3 md:mb-4">Inspection Points</div>
                <p class="text-muted-foreground leading-relaxed text-sm">Every Williams vehicle undergoes a rigorous mechanical and aesthetic audit before joining our collection.</p>
            </div>
            <div class="car-metric-card p-6 md:p-10 rounded-[2rem] md:rounded-[3rem] bg-card border border-border hover:bg-card/80 transition-all duration-500">
                <div class="text-4xl md:text-5xl font-black text-foreground mb-2">98%</div>
                <div class="text-accent text-xs md:text-sm font-bold uppercase tracking-widest mb-3 md:mb-4">Trust Factor</div>
                <p class="text-muted-foreground leading-relaxed text-sm">Our client retention rate reflects our commitment to cinematic transparency and post-sale care.</p>
            </div>
            <div class="car-metric-card p-6 md:p-10 rounded-[2rem] md:rounded-[3rem] bg-card border border-border hover:bg-card/80 transition-all duration-500">
                <div class="text-4xl md:text-5xl font-black text-foreground mb-2">GTA</div>
                <div class="text-accent text-xs md:text-sm font-bold uppercase tracking-widest mb-3 md:mb-4">Doorstep Delivery</div>
                <p class="text-muted-foreground leading-relaxed text-sm">Experience the thrill of your new car delivered right to your home, anywhere in the Greater Toronto Area.</p>
            </div>
        </div>
    </div>
</section>

<!-- Taxonomy Selection: Shop by Make & Type -->
<section class="py-16 bg-white dark:bg-gray-950 transition-colors duration-500 border-b border-border/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Shop by Make -->
        <div class="mb-16 reveal-section">
            <div class="flex justify-between items-end border-b border-gray-200 dark:border-gray-800 pb-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Shop by Make</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">You can search for cars by make.</p>
                </div>
                <a href="<?php echo url('brand-selection'); ?>" class="text-accent font-medium hover:text-accent/80 flex items-center gap-1 transition-colors">
                    View all makes <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-4 md:gap-6">
                <?php 
                $popularMakesStmt = $db->query("SELECT id, name, logo_url FROM makes WHERE is_popular = 1 ORDER BY name LIMIT 12");
                $popularMakes = $popularMakesStmt->fetchAll();
                foreach ($popularMakes as $make): 
                ?>
                <a href="<?php echo url('cars/' . strtolower(str_replace(' ', '-', $make['name']))); ?>" class="flex flex-col items-center gap-3 group">
                    <div class="h-16 w-16 md:h-20 md:w-20 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 dark:bg-gray-800 dark:border-gray-700 group-hover:shadow-md group-hover:border-accent/30 transition-all p-3">
                        <?php if ($make['logo_url']): ?>
                            <img src="<?php echo $make['logo_url']; ?>" alt="<?php echo clean($make['name']); ?>" class="w-full h-full object-contain group-hover:scale-110 transition-transform mix-blend-multiply dark:mix-blend-screen dark:invert">
                        <?php else: ?>
                            <span class="font-bold text-gray-400 text-xs"><?php echo substr($make['name'], 0, 3); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-accent transition-colors text-center"><?php echo clean($make['name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Shop by Type -->
        <div class="reveal-section">
            <div class="border-b border-gray-200 dark:border-gray-800 pb-4 mb-8">
                <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Shop by Type</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Here, you can find vehicles of any type</p>
            </div>
            
            <div class="grid grid-cols-4 md:grid-cols-8 gap-4 md:gap-6">
                <?php 
                $typesStmt = $db->query("SELECT id, name, icon_url FROM body_types ORDER BY name LIMIT 8");
                $types = $typesStmt->fetchAll();
                foreach ($types as $type): 
                ?>
                <a href="<?php echo url('cars/type/' . strtolower(str_replace(' ', '-', $type['name']))); ?>" class="flex flex-col items-center gap-4 group">
                    <div class="h-16 md:h-20 w-full flex items-center justify-center group-hover:-translate-y-2 transition-transform duration-300">
                        <?php if ($type['icon_url']): ?>
                            <img src="<?php echo $type['icon_url']; ?>" alt="<?php echo clean($type['name']); ?>" class="h-full object-contain filter drop-shadow-sm">
                        <?php else: ?>
                            <i class="fas fa-car-side text-4xl text-gray-300"></i>
                        <?php endif; ?>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-accent transition-colors text-center"><?php echo clean($type['name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<!-- Featured Showcase -->
<?php if (count($featuredCars) > 0): ?>
<section class="py-10 bg-muted transition-colors duration-500 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 mb-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
            <div class="reveal-section">
                <h2 class="text-5xl md:text-7xl font-black text-foreground mb-6 uppercase">CURATED <br> <span class="text-gradient">COLLECTION</span></h2>
                <p class="text-muted-foreground max-w-xl italic">Hand-picked premium vehicles currently available for private viewing.</p>
            </div>
            <div class="hidden md:block">
                <a href="<?php echo url('cars'); ?>" class="text-foreground font-bold border-b-2 border-accent pb-2 hover:text-accent transition-colors">Vew full catalog</a>
            </div>
        </div>
    </div>

    <div class="relative px-4 pb-5">
        <div class="swiper featured-carousel px-0">
            <div class="swiper-wrapper">
                <?php foreach ($featuredCars as $car):
                    $image =  url($car['primary_image']) ?? 'https://placehold.co/800x600?text=No+Image';
                ?>
                <div class="swiper-slide !w-[90vw] md:!w-[60vw]">
                    <div class="relative h-[450px] md:h-[600px] rounded-[2rem] md:rounded-[3rem] overflow-hidden group">
                        <img src="<?php echo $image; ?>" alt="<?php echo clean($car['make'] . ' ' . $car['model']); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                        
                        <!-- Favorite Heart (Same as Car Card) -->
                        <div x-data="{ 
                            isFavorited: <?php echo $car['is_favorited'] ? 'true' : 'false'; ?>,
                            isLoading: false,
                            toggleFavorite(carId) {
                                if (!window.isLoggedIn) {
                                    window.dispatchEvent(new CustomEvent('open-login-modal'));
                                    return;
                                }
                                this.isLoading = true;
                                fetch('<?php echo url('api/favorites'); ?>', {
                                    method: 'POST',
                                    headers: { 
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': window.csrfToken
                                    },
                                    body: JSON.stringify({ car_id: carId })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        this.isFavorited = (data.favorite_status === 'added');
                                        window.dispatchEvent(new CustomEvent('notify', {
                                            detail: {
                                                message: this.isFavorited ? window.i18n.added_to_favorites : window.i18n.removed_from_favorites,
                                                type: 'success'
                                            }
                                        }));
                                    }
                                })
                                .catch(err => console.error(err))
                                .finally(() => this.isLoading = false);
                            }
                        }" class="absolute top-6 right-6 z-30">
                            <button @click.prevent="toggleFavorite(<?php echo $car['id']; ?>)" 
                                    :class="isLoading ? 'opacity-50' : ''"
                                    class="w-12 h-12 rounded-full bg-white shadow-xl flex items-center justify-center text-red-500 hover:scale-110 transition-all">
                                <i class="fa-heart" :class="isFavorited ? 'fas' : 'far text-gray-400'"></i>
                            </button>
                        </div>

                        <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/20 to-transparent"></div>
                        <div class="absolute bottom-8 left-8 right-8 md:bottom-12 md:left-12 md:right-12 text-white">
                            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 md:gap-6">
                                <div>
                                    <span class="bg-accent text-[9px] md:text-[10px] font-black px-2 md:px-3 py-1 rounded-full uppercase tracking-tighter mb-2 md:mb-4 inline-block">Featured</span>
                                    <h3 class="text-2xl md:text-6xl font-black leading-none mb-2"><?php echo clean($car['year'] . ' ' . $car['make'] . ' ' . $car['model']); ?></h3>
                                    <div class="flex items-center gap-4 md:gap-6 text-gray-400 text-xs md:text-base">
                                        <span class="flex items-center gap-2"><i class="fas fa-tachometer-alt text-accent"></i> <?php echo formatMileage($car['mileage']); ?></span>
                                        <span class="flex items-center gap-2"><i class="fas fa-gas-pump text-accent"></i> <?php echo $car['fuel_type']; ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between md:justify-end gap-6 w-full md:w-auto">
                                    <span class="text-2xl md:text-5xl font-black text-white whitespace-nowrap"><?php echo formatPrice($car['price'], $car['price_unit'] ?? null); ?></span>
                                    <a href="<?php echo url('car-detail/' . $car['slug']); ?>" class="btn-premium bg-white text-primary px-6 md:px-8 py-3 md:py-4 rounded-xl md:rounded-2xl font-bold text-sm md:text-base hover:bg-accent hover:text-white transition-all shadow-xl">
                                        Discover
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Custom Navigation Buttons Below -->
        <div class="flex justify-center items-center gap-8 mt-12">
            <button class="swiper-button-prev-custom w-14 h-14 rounded-full border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white hover:border-accent transition-all duration-300 shadow-sm">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="swiper-pagination-custom flex gap-2"></div>
            <button class="swiper-button-next-custom w-14 h-14 rounded-full border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white hover:border-accent transition-all duration-300 shadow-sm">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Dynamic Inventory Section -->
<section id="inventory" class="py-10 bg-background transition-colors duration-500 relative" x-data="inventoryManager()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 md:mb-20 reveal-section">
            <h2 class="text-3xl md:text-5xl font-black text-foreground mb-6 uppercase tracking-tight">Current <span class="text-gradient">Showroom</span></h2>
            <div class="h-1 w-20 md:w-24 bg-accent mx-auto rounded-full"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="car-grid">
            <?php foreach ($latestCars as $car): ?>
                <?php renderCarCard($car, 'opacity-0 translate-y-2'); ?>
            <?php endforeach; ?>
        </div>

        <!-- Load More Control -->
        <div class="mt-24 text-center" x-show="hasMore">
            <button @click="loadMore()" 
                    :disabled="loading"
                    class="group relative inline-flex items-center gap-4 px-12 py-5 rounded-3xl bg-card border border-border text-foreground font-bold text-lg hover:bg-muted transition-all active:scale-95 disabled:opacity-50 shadow-md">
                <span x-text="loading ? 'SCANNING DATABASE...' : 'LOAD MORE VEHICLES'"></span>
                <i class="fas fa-plus text-accent group-hover:rotate-180 transition-transform duration-500" x-show="!loading"></i>
                <i class="fas fa-circle-notch fa-spin text-accent" x-show="loading"></i>
            </button>
        </div>
    </div>
</section>

<!-- Client Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="py-20 bg-muted transition-colors duration-500 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 mb-12">
        <div class="text-center reveal-section">
            <span class="text-accent font-bold tracking-[0.3em] uppercase text-xs mb-4 block">What Our Clients Say</span>
            <h2 class="text-4xl md:text-6xl font-black text-foreground mb-6 uppercase tracking-tighter">CLIENT <span class="text-gradient">VOICES</span></h2>
            <div class="h-1 w-20 bg-accent mx-auto rounded-full"></div>
        </div>
    </div>

    <div class="relative px-4">
        <div class="swiper testimonials-carousel">
            <div class="swiper-wrapper pb-4">
                <?php foreach ($testimonials as $t): ?>
                <div class="swiper-slide !w-[85vw] md:!w-[500px]">
                    <div class="glass p-8 md:p-10 rounded-[2.5rem] border border-border/50 h-full flex flex-col relative overflow-hidden group hover:border-accent/30 transition-all duration-500">
                        <!-- Decorative Quote -->
                        <div class="absolute top-6 right-8 text-accent/10 text-7xl font-black leading-none select-none">&ldquo;</div>

                        <!-- Stars -->
                        <div class="flex gap-1 mb-6">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fas fa-star text-sm <?php echo $s <= $t['rating'] ? 'text-accent' : 'text-border'; ?>"></i>
                            <?php endfor; ?>
                        </div>

                        <!-- Comment -->
                        <p class="text-foreground/80 leading-relaxed text-sm md:text-base mb-8 flex-1 italic">
                            &ldquo;<?php echo clean($t['comment']); ?>&rdquo;
                        </p>

                        <!-- Author -->
                        <div class="flex items-center gap-4 mt-auto pt-6 border-t border-border/30">
                            <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center text-accent font-black text-lg overflow-hidden">
                                <?php if (!empty($t['image_url'])): ?>
                                    <img src="<?php echo $t['image_url']; ?>" alt="<?php echo clean($t['name']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($t['name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-black text-foreground tracking-tight"><?php echo clean($t['name']); ?></p>
                                <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                                    <?php echo clean($t['location']); ?>
                                    <?php if ($t['make']): ?>
                                        <span class="text-accent">&bull; <?php echo clean($t['year'] . ' ' . $t['make'] . ' ' . $t['model']); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Modern CTA -->
<section class="py-10 relative overflow-hidden bg-background transition-colors duration-500">
    <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_30%_50%,rgba(249,115,22,0.1),transparent_50%)]"></div>
    <div class="max-w-5xl mx-auto px-4 text-center relative z-10">
        <h2 class="text-5xl md:text-8xl font-black text-foreground mb-10 tracking-tighter uppercase line-clamp-2">READY TO UPGRADE <br> YOUR <span class="text-gradient">LIFESTYLE?</span></h2>
        <div class="flex flex-wrap justify-center gap-6">
            <a href="<?php echo url('contact'); ?>" class="btn-premium bg-accent text-white px-12 py-6 rounded-3xl font-bold text-xl shadow-2xl">
                Contact Our Concierge
            </a>
            <a href="tel:+233202493547" class="px-12 py-6 rounded-3xl border border-border text-foreground font-bold text-xl hover:bg-muted transition-colors shadow-sm">
                Call Now
            </a>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Fallback: If GSAP fails, show key elements
        const fallback = setTimeout(() => {
            const items = document.querySelectorAll('.car-card, .reveal-section, .car-metric-card');
            items.forEach(c => {
                c.classList.add('loaded');
                c.classList.add('visible');
            });
        }, 1000);

        // --- GSAP Entrances (Optimized) ---
        const tl = gsap.timeline({
            defaults: { ease: "power3.out" },
            onComplete: () => clearTimeout(fallback)
        });

        // Hero Animation - smoother and faster
        tl.to("#hero-bg", { scale: 1, duration: 2, ease: "power2.out" })
          .from("#main-title", { y: 80, opacity: 0, duration: 1.2, ease: "power3.out" }, "-=1.2")
          .to("#hero-desc", { opacity: 1, y: 0, duration: 0.8 }, "-=0.6")
          .to("#hero-btns", { opacity: 1, y: 0, duration: 0.8 }, "-=0.6")
          .to("#scroll-hint", { opacity: 1, duration: 0.6 }, "-=0.4");

        // Scroll Triggers - optimized with better performance
        gsap.utils.toArray('.reveal-section').forEach(section => {
            gsap.from(section, {
                scrollTrigger: {
                    trigger: section,
                    start: "top 85%",
                    toggleActions: "play none none none",
                    once: true
                },
                y: 40,
                opacity: 0,
                duration: 0.8,
                ease: "power2.out"
            });
        });

        gsap.utils.toArray('.car-metric-card').forEach((card, i) => {
            gsap.from(card, {
                scrollTrigger: {
                    trigger: card,
                    start: "top 90%",
                    toggleActions: "play none none none",
                    once: true
                },
                y: 50,
                opacity: 0,
                duration: 0.8,
                delay: i * 0.15,
                ease: "power2.out"
            });
        });

        // Inventory Grid Animation - smoother stagger
        gsap.to("#car-grid .car-card", {
            scrollTrigger: {
                trigger: "#car-grid",
                start: "top 85%",
                toggleActions: "play none none none",
                once: true
            },
            y: 0,
            opacity: 1,
            duration: 0.6,
            stagger: 0.08,
            ease: "power2.out",
            clearProps: "all"
        });

        // Carousel Initialization - optimized
        new Swiper('.featured-carousel', {
            slidesPerView: 'auto',
            spaceBetween: 20,
            centeredSlides: true,
            loop: true,
            grabCursor: true,
            speed: 800,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            navigation: {
                nextEl: '.swiper-button-next-custom',
                prevEl: '.swiper-button-prev-custom',
            },
            pagination: {
                el: '.swiper-pagination-custom',
                clickable: true,
                renderBullet: function (index, className) {
                    return '<span class="' + className + ' w-2 h-2 bg-white/20 rounded-full transition-all duration-300 hover:bg-accent"></span>';
                }
            },
            effect: 'slide',
            keyboard: true,
            lazy: {
                loadPrevNext: true,
                loadPrevNextAmount: 2
            }
        });

        // Testimonials Carousel - optimized
        new Swiper('.testimonials-carousel', {
            slidesPerView: 'auto',
            spaceBetween: 24,
            centeredSlides: true,
            loop: true,
            grabCursor: true,
            speed: 700,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            breakpoints: {
                768: {
                    spaceBetween: 32
                }
            }
        });
    });

    // --- Alpine.js Manager ---
    function inventoryManager() {
        return {
            page: 1,
            loading: false,
            hasMore: true,
            async loadMore() {
                if (this.loading || !this.hasMore) return;
                
                this.page++;
                this.loading = true;
                
                const grid = document.getElementById('car-grid');
                
                // Add skeleton cards
                const skeletonHtml = `
                    <div class="car-card skeleton-card bg-white dark:bg-card rounded-[1.25rem] md:rounded-[1.5rem] overflow-hidden border border-border/10">
                        <div class="relative aspect-[4/3] bg-muted animate-pulse"></div>
                        <div class="p-3.5 md:p-5">
                            <div class="flex items-start gap-1.5 mb-2">
                                <div class="w-8 h-4 bg-muted rounded animate-pulse"></div>
                                <div class="w-3/4 h-5 bg-muted rounded animate-pulse"></div>
                            </div>
                            <div class="w-1/2 h-3 bg-muted rounded mb-3 animate-pulse"></div>
                            <div class="flex flex-col gap-1.5 mb-4">
                                <div class="w-1/3 h-4 bg-muted rounded animate-pulse"></div>
                                <div class="w-1/4 h-4 bg-muted rounded animate-pulse"></div>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-border/10">
                                <div class="w-1/3 h-6 bg-muted rounded animate-pulse"></div>
                                <div class="w-7 h-7 md:w-9 md:h-9 bg-muted rounded-full animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                for (let i = 0; i < 4; i++) {
                    grid.insertAdjacentHTML('beforeend', skeletonHtml);
                }
                
                try {
                    const response = await fetch(`<?php echo url('api/get-cars'); ?>?page=${this.page}`);
                    
                    // Remove skeleton cards if request succeeds
                    document.querySelectorAll('.skeleton-card').forEach(el => el.remove());
                    
                    if (response.status === 204) {
                        this.hasMore = false;
                        return;
                    }
                    
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newCards = Array.from(doc.querySelectorAll('.car-card'));
                    
                    newCards.forEach((card) => {
                        grid.appendChild(card);
                    });
                    
                    // Animate in new cards using fromTo
                    gsap.fromTo(newCards, 
                        { opacity: 0, y: 40 },
                        {
                            opacity: 1,
                            y: 0,
                            duration: 0.8,
                            stagger: 0.1,
                            ease: "power2.out",
                            clearProps: "all"
                        }
                    );
                    
                    if (newCards.length < 8) {
                        this.hasMore = false;
                    }
                    
                } catch (error) {
                    console.error('Error loading cars:', error);
                } finally {
                    // Ensure skeletons are removed on error as well
                    document.querySelectorAll('.skeleton-card').forEach(el => el.remove());
                    this.loading = false;
                }
            }
        }
    }
</script>
