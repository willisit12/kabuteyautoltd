<?php
/**
 * includes/component/car-card.php
 * Compact, modern car card component based on reference design
 */
function renderCarCard($car, $extraClasses = '') {
    if (!$car) return;

    $image = !empty($car['primary_image']) ? url($car['primary_image']) : 'https://placehold.co/400x300?text=No+Image';
    $name = clean($car['make'] . ' ' . $car['model'] . ' ' . $car['year'] . ' ' . ($car['trim'] ?? ''));
    $price = formatPrice($car['price'], $car['price_unit'] ?? null);
    $mileage = formatMileage($car['mileage']);
    $fuel = $car['fuel_type'];
    $year = $car['year'];

    // Map condition to Grade
    $condition = strtoupper($car['condition'] ?? 'EXCELLENT');
    $grade = 'B';
    $gradeColor = 'bg-[#00c58d]'; // Default Green (Grade A/B)
    $gradeText = 'text-white';

    if ($condition === 'EXCELLENT') {
        $grade = 'S';
        $gradeColor = 'bg-[#f5a623]'; // Gold/Orange (Grade S)
    } elseif ($condition === 'GOOD') {
        $grade = 'A';
    }

    ?>
    <div class="car-card bg-white dark:bg-card rounded-[1.25rem] md:rounded-[1.5rem] overflow-hidden group transition-all duration-500 hover:shadow-2xl border border-border/10 <?php echo $extraClasses; ?>">
        <!-- Image Container -->
        <div class="relative aspect-[4/3] overflow-hidden" 
             x-data="{ 
                isFavorited: <?php echo ($car['is_favorited'] ?? false) ? 'true' : 'false'; ?>,
                isLoading: false,
                toggleFavorite(carId) {
                    if (!isLoggedIn) {
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
                    .catch(err => console.error('Favorite toggle error:', err))
                    .finally(() => this.isLoading = false);
                }
             }">
            <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
            
            <!-- Buy Now Badge -->
            <div class="absolute top-0 left-0 bg-[#00c58d] text-white text-[8px] md:text-[11px] font-black px-2.5 py-1.5 md:px-4 md:py-2 rounded-br-[1rem] md:rounded-br-[1.25rem] shadow-sm uppercase tracking-tight z-10">
                Buy Now
            </div>

            <!-- Favorite Heart -->
            <button @click.prevent="toggleFavorite(<?php echo $car['id']; ?>)" 
                    :class="isLoading ? 'opacity-50 cursor-wait' : ''"
                    class="absolute top-3 right-3 md:top-4 md:right-4 w-8 h-8 md:w-10 md:h-10 rounded-full bg-white shadow-lg flex items-center justify-center text-red-500 hover:scale-110 active:scale-95 transition-all z-20">
                <i class="fa-heart" :class="isFavorited ? 'fas' : 'far text-gray-400'"></i>
            </button>
            
            <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Content Area -->
        <div class="p-3.5 md:p-5">
            <!-- Grade & Name -->
            <div class="flex items-start gap-1.5 mb-2">
                <div class="<?php echo $gradeColor; ?> <?php echo $gradeText; ?> text-[9px] font-black px-1.5 py-0.5 rounded-[4px] flex items-center gap-1 shrink-0 mt-0.5">
                    <span class="opacity-70 text-[7px] uppercase">Grade</span>
                    <span><?php echo $grade; ?></span>
                </div>
                <h3 class="text-[13px] md:text-base font-bold text-foreground leading-tight line-clamp-2 hover:text-accent transition-colors">
                    <a href="<?php echo url('car-detail/' . $car['slug']); ?>">
                        Used <?php echo $name; ?>
                    </a>
                </h3>
            </div>

            <!-- Metadata Line -->
            <p class="text-[10px] md:text-[11px] font-medium text-muted-foreground/60 mb-3">
                <?php echo $year; ?>.<?php echo date('m', strtotime($car['created_at'])); ?> | <?php echo $mileage; ?> | <?php echo $fuel; ?>
            </p>

            <!-- Tags Section -->
            <div class="flex flex-col gap-1.5 mb-4">
                <div class="inline-flex self-start border border-[#f5a623]/30 bg-[#f5a623]/5 text-[#f5a623] text-[9px] md:text-[10px] font-bold px-2 py-0.5 rounded-sm">
                    Direct Sourcing
                </div>
                <div class="text-[#00c58d] text-[9px] md:text-[10px] font-bold flex items-center gap-1.5">
                    <i class="fas fa-check-circle text-[10px]"></i>
                    Guazi Inspected
                </div>
                <?php if ($car['featured']): ?>
                    <div class="text-accent text-[9px] md:text-[10px] font-bold flex items-center gap-1.5">
                        <i class="fas fa-star text-[10px]"></i>
                        Nearly New
                    </div>
                <?php endif; ?>
            </div>

            <!-- Price Row -->
            <div class="flex items-center justify-between pt-3 border-t border-border/10">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col -space-y-1">
                        <span class="text-[8px] md:text-[9px] text-muted-foreground font-black uppercase tracking-tighter">FOB</span>
                        <span class="text-[8px] md:text-[9px] text-muted-foreground font-black uppercase tracking-tighter">Price:</span>
                    </div>
                    <span class="text-base md:text-xl font-black text-foreground tracking-tighter"><?php echo $price; ?></span>
                </div>
                <a href="<?php echo url('car-detail/' . $car['slug']); ?>" class="w-7 h-7 md:w-9 md:h-9 rounded-full bg-slate-100 dark:bg-muted flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>
    </div>
    <?php
}
