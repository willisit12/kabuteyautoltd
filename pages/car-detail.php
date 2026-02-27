<?php
/**
 * pages/car-detail.php
 * Single Vehicle Detail Template
 */

$id = intval($_GET['id'] ?? 0);
$slug = clean($_GET['slug'] ?? '');

if ($slug) {
    $car = getCarBySlug($slug);
} else {
    $car = getCarById($id);
}

if (!$car) {
    http_response_code(404);
    include_once __DIR__ . '/404.php';
    exit;
}

$pageTitle = $car['year'] . ' ' . $car['make'] . ' ' . $car['model'];
include_once __DIR__ . '/../includes/layout/header.php';
?>

<section class="relative pt-32 pb-20 overflow-hidden bg-background transition-colors duration-500">
    <!-- Background Accents -->
    <div class="absolute inset-0 z-0 opacity-20 dark:opacity-10 pointer-events-none">
        <div class="absolute top-0 left-[-10%] w-[600px] h-[600px] bg-accent/15 rounded-full blur-[140px]"></div>
        <div class="absolute bottom-0 right-[-10%] w-[500px] h-[500px] bg-accent/10 rounded-full blur-[120px]"></div>
    </div>

    <style>
        :root {
            --accent: #f97316;
        }
        .thumbsSwiper .swiper-slide {
            opacity: 0.4;
            transition: opacity 0.3s ease;
        }
        .thumbsSwiper .swiper-slide-thumb-active {
            opacity: 1;
        }
        .thumbsSwiper .swiper-slide img {
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .thumbsSwiper .swiper-slide-thumb-active img {
            border-color: var(--accent);
            box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);
        }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="reveal-section mb-12">
            <a href="<?php echo url('cars'); ?>" class="group inline-flex items-center gap-3 text-foreground/60 hover:text-accent font-bold transition-all uppercase tracking-widest text-xs">
                <span class="w-10 h-10 rounded-full border border-border flex items-center justify-center group-hover:border-accent group-hover:bg-accent/5 transition-all">
                    <i class="fas fa-arrow-left"></i>
                </span>
                Showroom Inventory
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24">
            <!-- Immersive Gallery -->
            <div class="reveal-section">
                <div class="relative group/gallery">
                    <!-- Main Swiper -->
                    <div class="swiper detailSwiper rounded-[3rem] overflow-hidden shadow-2xl h-[400px] md:h-[550px] border border-border/50">
                        <div class="swiper-wrapper">
                            <?php foreach ($car['images'] as $image): ?>
                            <div class="swiper-slide cursor-zoom-in">
                                <img src="<?php echo url(clean($image['url'])); ?>" alt="Vehicle Detail" class="w-full h-full object-cover transition-transform duration-1000 group-hover/gallery:scale-105">
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($car['images'])): ?>
                                <div class="swiper-slide">
                                    <div class="w-full h-full bg-muted flex flex-col items-center justify-center text-muted-foreground">
                                        <i class="fas fa-camera-retro text-6xl mb-4 opacity-20"></i>
                                        <p class="font-bold uppercase tracking-widest text-xs">Awaiting Gallery Upload</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Navigation -->
                        <div class="absolute bottom-10 right-10 flex gap-4 z-20">
                            <button class="swiper-prev-detail w-12 h-12 rounded-full glass border border-white/20 text-white flex items-center justify-center hover:bg-accent hover:border-accent transition-all shadow-lg active:scale-90">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <button class="swiper-next-detail w-12 h-12 rounded-full glass border border-white/20 text-white flex items-center justify-center hover:bg-accent hover:border-accent transition-all shadow-lg active:scale-90">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Thumbnails Swiper -->
                    <div class="mt-6">
                        <div class="swiper thumbsSwiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($car['images'] as $image): ?>
                                <div class="swiper-slide cursor-pointer">
                                    <div class="aspect-video rounded-2xl overflow-hidden border border-border/50 bg-muted/30 glass">
                                        <img src="<?php echo url(clean($image['url'])); ?>" alt="Thumbnail" class="w-full h-full object-cover">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Vehicle Narrative & Specs -->
            <div class="flex flex-col">
                <div class="reveal-section mb-10">
                    <div class="flex items-center gap-4 mb-4">
                        <span class="w-12 h-[2px] bg-accent"></span>
                        <span class="text-accent font-black uppercase tracking-[0.4em] text-xs"><?php echo __('key_features'); ?></span>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-block text-accent font-black tracking-[0.3em] uppercase text-[10px]">
                            Ref. ID: #WAMS-<?php echo sprintf("%04d", $car['id']); ?>
                        </span>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo url('admin/cars/edit.php?id=' . $car['id']); ?>" class="w-6 h-6 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent hover:bg-accent hover:text-white transition-all shadow-sm" title="Edit Vehicle">
                                <i class="fas fa-pencil text-[10px]"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-4xl md:text-7xl font-black text-foreground mb-4 tracking-tighter uppercase leading-[0.9]">
                        <?php echo clean($car['make']); ?> <br>
                        <span class="text-gradient"><?php echo clean($car['model']); ?></span>
                    </h1>
                    <div class="flex items-center gap-6 mt-6">
                        <div class="text-3xl md:text-5xl font-black text-foreground tracking-tighter">
                            <?php echo formatPrice($car['price']); ?>
                        </div>
                        <div class="h-8 w-[1px] bg-border/20"></div>
                        <div class="px-4 py-1.5 rounded-full bg-accent/10 border border-accent/20 text-accent font-black text-[10px] uppercase tracking-widest">
                            <?php echo clean($car['condition']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Spec Grid -->
                <div class="reveal-section glass rounded-[2.5rem] p-8 md:p-10 mb-10 border border-border/50 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <i class="fas fa-shield-check text-6xl text-foreground"></i>
                    </div>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground/40 mb-8 flex items-center gap-3">
                        <span class="w-2 h-2 bg-accent rounded-full animate-pulse"></span>
                        Mechanical Highlights
                    </h4>
                    <div class="grid grid-cols-2 gap-y-8 gap-x-12">
                        <div class="group/spec">
                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2 group-hover/spec:text-accent transition-colors underline decoration-accent/20 decoration-2 underline-offset-4">Odometer</span>
                            <span class="text-lg md:text-xl font-black text-foreground tracking-tighter"><?php echo formatMileage($car['mileage']); ?></span>
                        </div>
                        <div class="group/spec">
                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2 group-hover/spec:text-accent transition-colors underline decoration-accent/20 decoration-2 underline-offset-4">Drive Train</span>
                            <span class="text-lg md:text-xl font-black text-foreground tracking-tighter"><?php echo clean($car['transmission']); ?></span>
                        </div>
                        <div class="group/spec">
                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2 group-hover/spec:text-accent transition-colors underline decoration-accent/20 decoration-2 underline-offset-4">Energy Source</span>
                            <span class="text-lg md:text-xl font-black text-foreground tracking-tighter"><?php echo clean($car['fuel_type']); ?></span>
                        </div>
                        <div class="group/spec">
                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest block mb-2 group-hover/spec:text-accent transition-colors underline decoration-accent/20 decoration-2 underline-offset-4">Exterior Shade</span>
                            <span class="text-lg md:text-xl font-black text-foreground tracking-tighter"><?php echo clean($car['color']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="reveal-section glass rounded-[2.5rem] p-8 md:p-10 mb-12 border border-border/50 shadow-xl">
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground/40 mb-6 italic">Vehicle Narrative</h4>
                    <div class="prose prose-accent dark:prose-invert max-w-none">
                        <p class="text-muted-foreground leading-relaxed font-medium italic">
                            <?php echo nl2br(clean($car['description'])); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="reveal-section flex flex-col sm:flex-row gap-6">
                    <a href="<?php echo url('contact?id=' . $car['id']); ?>" class="flex-1 btn-premium bg-accent text-white text-center py-6 rounded-3xl font-black uppercase tracking-tighter text-lg shadow-[0_15px_40px_rgba(249,115,22,0.4)] hover:scale-[1.02] active:scale-95 transition-all">
                        Initiate Acquisition
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo url('admin/cars/edit.php?id=' . $car['id']); ?>" class="w-full sm:w-20 h-20 rounded-3xl bg-foreground text-background hover:bg-accent transition-all flex items-center justify-center shadow-xl group border border-transparent">
                            <i class="fas fa-edit text-xl group-hover:scale-110 transition-transform"></i>
                        </a>
                    <?php endif; ?>
                    <button class="w-full sm:w-20 h-20 rounded-3xl bg-muted border border-border text-foreground hover:bg-foreground hover:text-background transition-all flex items-center justify-center shadow-sm">
                        <i class="fas fa-share-nodes text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Recommended Inventory -->
        <?php
        $recommendedCars = getRecommendedCars($car['id'], 3);
        if (count($recommendedCars) > 0):
        ?>
        <div class="mt-32 pt-16 border-t border-border/20 reveal-section">
            <div class="flex items-end justify-between mb-12">
                <div>
                    <span class="inline-block text-accent font-black tracking-[0.2em] uppercase text-[10px] mb-4">
                        Curated For You
                    </span>
                    <h2 class="text-3xl md:text-5xl font-black text-foreground uppercase tracking-tighter">
                        Similar <span class="text-gradient">Vehicles</span>
                    </h2>
                </div>
                <a href="<?php echo url('cars'); ?>" class="hidden md:inline-flex text-xs font-bold uppercase tracking-widest text-foreground/60 hover:text-accent transition-colors items-center gap-2 group">
                    View All Inventory <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($recommendedCars as $recCar): 
                    $image = $recCar['primary_image'] ?? 'https://via.placeholder.com/400x300?text=No+Image';
                ?>
                <div class="bg-card backdrop-blur-md rounded-[2rem] overflow-hidden border border-border hover:border-accent/50 transition-all duration-700 group hover:-translate-y-2 shadow-sm hover:shadow-xl">
                    <div class="relative h-48 md:h-56 overflow-hidden">
                        <img src="<?php echo url($image); ?>" alt="<?php echo clean($recCar['make'] . ' ' . $recCar['model']); ?>" class="w-full h-full object-cover transition duration-1000 group-hover:scale-110 group-hover:rotate-1">
                        <div class="absolute inset-0 bg-gradient-to-t from-background/90 via-transparent to-transparent opacity-80"></div>
                        <div class="absolute bottom-4 left-4">
                            <h3 class="text-xl md:text-2xl font-black text-white group-hover:text-accent transition-colors tracking-tighter leading-none"><?php echo clean($recCar['make'] . ' ' . $recCar['model']); ?></h3>
                            <span class="text-white/80 text-[10px] font-black uppercase tracking-[0.2em] block mt-1"><?php echo $recCar['year']; ?> | <?php echo formatMileage($recCar['mileage']); ?></span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-black text-foreground tracking-tighter"><?php echo formatPrice($recCar['price']); ?></span>
                                    <a href="<?php echo url('car-detail/' . $recCar['slug']); ?>" 
                                       class="p-4 bg-muted/50 rounded-xl hover:bg-accent hover:text-white transition-all group/btn border border-border/50"
                                       title="<?php echo __('view_details'); ?>">
                                        <i class="fas fa-external-link-alt group-hover/btn:scale-110 transition-transform"></i>
                                    </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-10 text-center md:hidden">
                <a href="<?php echo url('cars'); ?>" class="inline-flex btn-premium bg-muted hover:bg-accent text-foreground hover:text-white px-8 py-4 rounded-2xl font-black uppercase tracking-tighter text-xs transition-all shadow-sm">
                    View All Inventory
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- Fullscreen Lightbox Modal -->
<div id="imageLightbox" class="fixed inset-0 z-[100] bg-background/95 backdrop-blur-xl opacity-0 pointer-events-none transition-opacity duration-500 flex items-center justify-center hidden">
    <button id="closeLightbox" class="absolute top-8 right-8 w-14 h-14 rounded-full bg-muted/50 border border-border/50 text-foreground flex items-center justify-center hover:bg-accent hover:text-white hover:border-accent transition-all z-20 group">
        <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform duration-300"></i>
    </button>
    
    <div class="w-full max-w-7xl px-4 md:px-12 h-[80vh] relative">
        <div class="swiper lightboxSwiper w-full h-full rounded-[2rem] overflow-hidden shadow-2xl">
            <div class="swiper-wrapper">
                <?php foreach ($car['images'] as $image): ?>
                <div class="swiper-slide flex items-center justify-center">
                    <img src="<?php echo url(clean($image['url'])); ?>" alt="Vehicle Detail Full" class="max-w-full max-h-full object-contain rounded-xl">
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="absolute inset-y-0 left-4 md:left-8 flex items-center z-10 pointer-events-none">
                <button class="swiper-prev-lightbox pointer-events-auto w-14 h-14 rounded-full bg-background/50 backdrop-blur-md border border-border text-foreground flex items-center justify-center hover:bg-accent hover:text-white hover:border-accent transition-all shadow-lg">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            <div class="absolute inset-y-0 right-4 md:right-8 flex items-center z-10 pointer-events-none">
                <button class="swiper-next-lightbox pointer-events-auto w-14 h-14 rounded-full bg-background/50 backdrop-blur-md border border-border text-foreground flex items-center justify-center hover:bg-accent hover:text-white hover:border-accent transition-all shadow-lg">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="swiper-pagination-lightbox absolute bottom-8 left-0 right-0 flex justify-center z-10"></div>
        </div>
        
        <div class="text-center mt-6 text-foreground/60 font-medium tracking-widest uppercase text-xs">
            <?php echo clean($car['year'] . ' ' . $car['make'] . ' ' . $car['model']); ?>
        </div>
    </div>
</div>

<style>
    /* Lightbox Styles */
    #imageLightbox.active {
        opacity: 1;
        pointer-events: auto;
    }
    .swiper-pagination-lightbox .swiper-pagination-bullet {
        width: 10px;
        height: 10px;
        background: var(--foreground, #fff);
        opacity: 0.3;
        transition: all 0.3s ease;
    }
    .swiper-pagination-lightbox .swiper-pagination-bullet-active {
        background: var(--accent);
        opacity: 1;
        transform: scale(1.3);
    }
</style>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Swiper Initialization
        const thumbsSwiper = new Swiper('.thumbsSwiper', {
            spaceBetween: 12,
            slidesPerView: 3,
            freeMode: true,
            watchSlidesProgress: true,
            breakpoints: {
                640: { slidesPerView: 4 },
                1024: { slidesPerView: 5 }
            }
        });

        new Swiper('.detailSwiper', {
            loop: true,
            speed: 1000,
            effect: 'fade',
            fadeEffect: { crossFade: true },
            navigation: { 
                nextEl: '.swiper-next-detail', 
                prevEl: '.swiper-prev-detail' 
            },
            thumbs: {
                swiper: thumbsSwiper
            },
            autoplay: {
                delay: 6000,
                disableOnInteraction: true
            }
        });

        // Lightbox Functionality
        const lightbox = document.getElementById('imageLightbox');
        const closeBtn = document.getElementById('closeLightbox');
        const galleryImages = document.querySelectorAll('.detailSwiper .swiper-slide img');
        
        let lightboxSwiper;

        function initLightboxSwiper() {
            if (!lightboxSwiper) {
                lightboxSwiper = new Swiper('.lightboxSwiper', {
                    loop: true,
                    speed: 600,
                    effect: 'cube',
                    cubeEffect: {
                        shadow: true,
                        slideShadows: true,
                        shadowOffset: 20,
                        shadowScale: 0.94,
                    },
                    keyboard: {
                        enabled: true,
                        onlyInViewport: false,
                    },
                    navigation: {
                        nextEl: '.swiper-next-lightbox',
                        prevEl: '.swiper-prev-lightbox',
                    },
                    pagination: {
                        el: '.swiper-pagination-lightbox',
                        clickable: true,
                    }
                });
            }
        }

        // Open Lightbox
        galleryImages.forEach((img) => {
            img.addEventListener('click', (e) => {
                // Find the closest slide to the clicked image
                const slide = e.target.closest('.swiper-slide');
                
                // Get the real index from the Swiper API or data attribute
                // Swiper adds data-swiper-slide-index to looped slides
                let realIndex = 0;
                if (slide.hasAttribute('data-swiper-slide-index')) {
                    realIndex = parseInt(slide.getAttribute('data-swiper-slide-index'), 10);
                } else {
                    // Fallback for non-looped or if attribute is missing
                    realIndex = Array.from(slide.parentNode.children).indexOf(slide);
                }
                
                lightbox.classList.remove('hidden');
                // Small delay to allow display block to take effect before animating opacity
                setTimeout(() => {
                    lightbox.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                    
                    initLightboxSwiper();
                    lightboxSwiper.update();
                    lightboxSwiper.slideToLoop(realIndex, 0);
                }, 10);
            });
        });

        // Close Lightbox
        function closeLightbox() {
            lightbox.classList.remove('active');
            setTimeout(() => {
                lightbox.classList.add('hidden');
                document.body.style.overflow = '';
            }, 500); // Wait for transition
        }

        closeBtn.addEventListener('click', closeLightbox);
        
        // Close on esc key or outside click
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                closeLightbox();
            }
        });
        
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        // GSAP Entrance
        const tl = gsap.timeline({ defaults: { ease: "power4.out" }});
        
        tl.from(".reveal-section", {
            y: 40,
            opacity: 0,
            duration: 1.2,
            stagger: 0.15,
            clearProps: "all"
        });
    });
</script>
