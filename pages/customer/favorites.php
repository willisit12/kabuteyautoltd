<?php
/**
 * pages/customer/favorites.php
 * Saved Vehicles management
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/customer-layout.php';

requireAuth();
$user = getUserInfo();
$db = getDB();

// Handle removal if needed (simple POST for now)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $stmt = $db->prepare("DELETE FROM favorites WHERE car_id = ? AND user_id = ?");
    $stmt->execute([$_POST['remove_id'], $user['id']]);
    setFlash('success', 'Vehicle removed from curated collection.');
    redirect(url('customer/favorites'));
}

$stmt = $db->prepare("
    SELECT f.*, c.make, c.model, c.year, c.slug, c.price, c.price_unit, c.status as car_status,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as primary_image
    FROM favorites f
    JOIN cars c ON f.car_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user['id']]);
$favorites = $stmt->fetchAll();

$success = getFlash('success');

ob_start();
?>

<div class="mb-12">
    <h1 class="text-4xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">Curated <span class="text-gradient">Wishlist.</span></h1>
    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Your hand-picked automotive masterpieces</p>
</div>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold reveal-content">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (empty($favorites)): ?>
    <div class="glass p-20 rounded-[4rem] border border-dashed border-border/50 text-center reveal-content">
        <div class="w-24 h-24 bg-accent/5 rounded-[2rem] flex items-center justify-center mx-auto mb-8 text-accent/20">
            <i class="fas fa-heart text-5xl"></i>
        </div>
        <h3 class="text-2xl font-black text-foreground tracking-tighter uppercase mb-2">Collection Empty</h3>
        <p class="text-sm font-medium text-muted-foreground italic max-w-sm mx-auto mb-10">Start saving vehicles to build your personal luxury fleet for future acquisition.</p>
        <a href="<?php echo url('cars'); ?>" class="inline-flex items-center gap-4 px-10 py-6 bg-accent text-white rounded-[2rem] font-black uppercase tracking-widest text-[11px] shadow-[0_15px_40px_rgba(249,115,22,0.3)] hover:scale-105 transition-all">
            <i class="fas fa-search"></i>
            Explore Active Showroom
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <?php foreach ($favorites as $fv): ?>
            <div class="glass rounded-[3.5rem] border border-border/50 overflow-hidden shadow-2xl group hover:border-accent transition-all duration-500 flex flex-col">
                <div class="relative aspect-video overflow-hidden">
                    <img src="<?php echo url($fv['primary_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    <div class="absolute top-6 right-6">
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="remove_id" value="<?php echo $fv['car_id']; ?>">
                            <button type="submit" class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-red-500 hover:border-red-500 transition-all flex items-center justify-center shadow-lg">
                                <i class="fas fa-trash-can text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="p-8 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h2 class="text-xl font-black text-foreground tracking-tighter uppercase leading-none"><?php echo $fv['year'] . ' ' . clean($fv['make'] . ' ' . $fv['model']); ?></h2>
                                <p class="text-[10px] font-bold text-accent uppercase tracking-widest mt-2">Active Spec</p>
                            </div>
                            <p class="text-lg font-black text-foreground tabular-nums tracking-tighter"><?php echo formatPrice($fv['price'], $fv['price_unit'] ?? null); ?></p>
                        </div>
                        <div class="flex gap-4 mb-8">
                            <span class="px-3 py-1 rounded-full bg-muted border border-border/30 text-[8px] font-black uppercase tracking-widest text-muted-foreground">Certified</span>
                            <span class="px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-[8px] font-black uppercase tracking-widest text-green-500 inline-flex items-center gap-1.5">
                                <div class="w-1 h-1 bg-green-500 rounded-full"></div>
                                <?php echo $fv['car_status']; ?>
                            </span>
                        </div>
                    </div>
                    <a href="<?php echo url('car-detail/' . $fv['slug']); ?>" class="w-full py-5 rounded-2xl bg-foreground text-background font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-accent hover:text-white transition-all shadow-md">
                        Initiate Intelligence Review
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'My Wishlist');
?>
