<?php
/**
 * pages/api/get-cars.php
 * Endpoint for "Load More" functionality
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$page = intval($_GET['page'] ?? 1);
$limit = 6;
$offset = ($page - 1) * $limit;

$filters = [
    'make' => $_GET['make'] ?? '',
    'model' => $_GET['model'] ?? '',
    'year' => $_GET['year'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? ''
];

$cars = searchCars($filters, $limit, $offset);

if (empty($cars)) {
    http_response_code(204); // No Content
    exit;
}

foreach ($cars as $car):
    $image = $car['primary_image'] ?? 'https://via.placeholder.com/400x300?text=No+Image';
?>
<div class="car-card bg-card backdrop-blur-md rounded-[2.5rem] overflow-hidden border border-border hover:border-accent/50 transition-all duration-700 group opacity-0 translate-y-10">
    <div class="relative h-64 md:h-72 overflow-hidden">
        <img src="<?php echo $image; ?>" alt="<?php echo clean($car['make'] . ' ' . $car['model']); ?>" class="w-full h-full object-cover transition duration-1000 group-hover:scale-110 group-hover:rotate-1">
        <div class="absolute inset-0 bg-gradient-to-t from-background/90 via-transparent to-transparent opacity-80"></div>
        <?php if ($car['featured']): ?>
            <div class="absolute top-6 right-6 bg-accent text-white text-[9px] md:text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-widest shadow-lg">Featured Element</div>
        <?php endif; ?>
    </div>
    <div class="p-8 md:p-10 flex flex-col h-full">
        <div class="flex justify-between items-start mb-6">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-8 h-[1px] bg-accent"></span>
                    <span class="text-accent text-[10px] font-black uppercase tracking-widest"><?php echo clean($car['year']); ?> Collection</span>
                </div>
                <h3 class="text-2xl md:text-3xl font-black text-foreground group-hover:text-accent transition-colors tracking-tighter leading-tight mb-1">
                    <?php echo clean($car['make'] . ' ' . $car['model']); ?>
                </h3>
                <p class="text-muted-foreground/60 text-[10px] font-medium uppercase tracking-[0.2em] italic"><?php echo __('authenticity_guaranteed'); ?></p>
            </div>
            <div class="text-right shrink-0 ml-4">
                <span class="text-2xl md:text-3xl font-black text-foreground tracking-tighter whitespace-nowrap"><?php echo formatPrice($car['price']); ?></span>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-6 mb-10 py-6 border-y border-border/10">
            <div class="flex items-center gap-3 text-muted-foreground text-xs font-bold uppercase tracking-wider">
                <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center text-accent">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <?php echo formatMileage($car['mileage']); ?>
            </div>
            <div class="flex items-center gap-3 text-muted-foreground text-xs font-bold uppercase tracking-wider">
                <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center text-accent">
                    <i class="fas fa-gas-pump"></i>
                </div>
                <?php echo $car['fuel_type']; ?>
            </div>
        </div>
        
        <a href="<?php echo url('car-detail/' . $car['slug']); ?>" class="btn-premium block w-full text-center bg-muted hover:bg-accent text-foreground hover:text-white py-5 rounded-2xl font-black uppercase tracking-tighter transition-all border border-border hover:border-accent text-sm shadow-sm group/btn">
            <?php echo __('view_details'); ?> <i class="fas fa-arrow-right ml-2 text-accent group-hover/btn:text-white transition-colors"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
