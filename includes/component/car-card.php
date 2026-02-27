<?php
/**
 * includes/component/car-card.php
 * Compact, modern car card component based on reference design
 */
function renderCarCard($car, $extraClasses = '') {
    if (!$car) return;

    $image = !empty($car['primary_image']) ? url($car['primary_image']) : 'https://via.placeholder.com/400x300?text=No+Image';
    $name = clean($car['make'] . ' ' . $car['model'] . ' ' . $car['year'] . ' ' . ($car['trim'] ?? ''));
    $price = formatPrice($car['price']);
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
    <div class="car-card bg-white dark:bg-card rounded-[1.5rem] overflow-hidden group transition-all duration-500 hover:shadow-2xl border border-border/10 <?php echo $extraClasses; ?>">
        <!-- Image Container -->
        <div class="relative aspect-[4/3] overflow-hidden">
            <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
            
            <!-- Buy Now Badge -->
            <div class="absolute top-3 left-3 bg-[#00c58d] text-white text-[10px] font-black px-3 py-1 rounded-md shadow-sm">
                Buy Now
            </div>
            
            <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>

        <!-- Content Area -->
        <div class="p-4 md:p-5">
            <!-- Grade & Name -->
            <div class="flex items-start gap-2 mb-2">
                <div class="<?php echo $gradeColor; ?> <?php echo $gradeText; ?> text-[8px] font-black px-2 py-0.5 rounded-sm flex items-center gap-1 shrink-0 mt-1">
                    <span class="opacity-70">Grade</span>
                    <span class="text-[10px]"><?php echo $grade; ?></span>
                </div>
                <h3 class="text-sm md:text-base font-bold text-foreground leading-tight line-clamp-2 hover:text-accent transition-colors">
                    <a href="<?php echo url('car-detail/' . $car['slug']); ?>">
                        Used <?php echo $name; ?>
                    </a>
                </h3>
            </div>

            <!-- Metadata Line -->
            <p class="text-[11px] font-medium text-muted-foreground mb-3">
                <?php echo $year; ?>.<?php echo date('m', strtotime($car['created_at'])); ?> | <?php echo $mileage; ?> | <?php echo $fuel; ?>
            </p>

            <!-- Tags Section -->
            <div class="flex flex-wrap gap-2 mb-4">
                <div class="border border-[#f5a623]/30 bg-[#f5a623]/5 text-[#f5a623] text-[10px] font-bold px-2 py-0.5 rounded-sm">
                    Direct Sourcing
                </div>
                <div class="text-[#00c58d] text-[10px] font-bold flex items-center gap-1">
                    <i class="fas fa-check-circle"></i>
                    Guazi Inspected
                </div>
                <?php if ($car['featured']): ?>
                    <div class="text-accent text-[10px] font-bold flex items-center gap-1">
                        <i class="fas fa-star"></i>
                        Nearly New
                    </div>
                <?php endif; ?>
            </div>

            <!-- Price Row -->
            <div class="flex items-center justify-between pt-3 border-t border-border/10">
                <div class="flex items-baseline gap-1">
                    <span class="text-[10px] text-muted-foreground font-bold">FOB Price:</span>
                    <span class="text-lg md:text-xl font-black text-foreground tracking-tighter"><?php echo $price; ?></span>
                </div>
                <a href="<?php echo url('car-detail/' . $car['slug']); ?>" class="w-8 h-8 rounded-full bg-muted flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        </div>
    </div>
    <?php
}
