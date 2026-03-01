<?php
/**
 * pages/customer/dashboard.php - Customer Intelligence Portal
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/customer-layout.php';

// Security check
requireAuth();
$user = getUserInfo();

// Fetch customer specific data
$db = getDB();

// 1. Orders (Acquisitions)
$stmt = $db->prepare("
    SELECT o.*, c.make, c.model, c.year, c.slug, c.price_unit,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as primary_image
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

// 2. Favorites (Wishlist)
$stmt = $db->prepare("
    SELECT f.*, c.make as make_name, c.model, c.year, c.slug, c.price, c.price_unit,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as primary_image
    FROM favorites f
    JOIN cars c ON f.car_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user['id']]);
$favorites = $stmt->fetchAll();

// 3. Inquiries (Concierge Correspondence)
$stmt = $db->prepare("
    SELECT i.*, c.make, c.model, c.year, c.slug
    FROM inquiries i
    LEFT JOIN cars c ON i.car_id = c.id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$user['id']]);
$inquiries = $stmt->fetchAll();

$success = getFlash('success');

ob_start();
?>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-12 flex items-center gap-4 text-sm font-bold relative z-10 reveal-content">
        <i class="fas fa-check-circle text-xl"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- Stats Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-16 reveal-content">
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl group hover:border-accent transition-all">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-4">Acquisitions</h4>
        <div class="flex items-end justify-between">
            <span class="text-4xl font-black text-foreground tracking-tighter"><?php echo count($orders); ?></span>
            <i class="fas fa-shopping-bag text-accent/20 text-3xl group-hover:text-accent transition-colors"></i>
        </div>
    </div>
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl group hover:border-accent transition-all">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-4">Saved Masterpieces</h4>
        <div class="flex items-end justify-between">
            <span class="text-4xl font-black text-foreground tracking-tighter"><?php echo count($favorites); ?></span>
            <i class="fas fa-heart text-accent/20 text-3xl group-hover:text-accent transition-colors"></i>
        </div>
    </div>
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl group hover:border-accent transition-all">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-4">Correspondence</h4>
        <div class="flex items-end justify-between">
            <span class="text-4xl font-black text-foreground tracking-tighter"><?php echo count($inquiries); ?></span>
            <i class="fas fa-comments text-accent/20 text-3xl group-hover:text-accent transition-colors"></i>
        </div>
    </div>
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl group hover:border-accent transition-all bg-accent/5">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-accent mb-4">Membership Level</h4>
        <div class="flex items-end justify-between">
            <span class="text-2xl font-black text-foreground tracking-tighter uppercase italic">Inner Circle</span>
            <i class="fas fa-crown text-accent text-3xl"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Left Side: Orders & Inquiries -->
    <div class="lg:col-span-2 space-y-12 reveal-content">
        
        <!-- Orders Tracking -->
        <section>
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-black uppercase tracking-tighter text-foreground flex items-center gap-3">
                    <i class="fas fa-truck-loading text-accent"></i>
                    Recent Acquisitions
                </h3>
                <a href="<?php echo url('customer/orders'); ?>" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">View All History</a>
            </div>
            
            <div class="space-y-6">
                <?php if (empty($orders)): ?>
                    <div class="glass p-12 rounded-[3rem] border border-dashed border-border/50 text-center">
                        <i class="fas fa-box-open text-4xl text-muted-foreground/30 mb-4"></i>
                        <p class="text-sm font-bold text-muted-foreground italic">No active car acquisitions found.</p>
                        <a href="<?php echo url('cars'); ?>" class="inline-block mt-6 text-accent font-black uppercase tracking-widest text-[10px] hover:underline">Explore Inventory</a>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                        <div class="glass p-6 rounded-[2.5rem] border border-border/50 shadow-lg flex flex-col sm:flex-row items-center gap-6 group hover:border-accent transition-all">
                            <div class="w-32 h-24 rounded-2xl overflow-hidden shadow-md">
                                <img src="<?php echo url($order['primary_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="">
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mb-2">
                                    <h4 class="text-lg font-black text-foreground tracking-tight"><?php echo $order['year'] . ' ' . clean($order['make'] . ' ' . $order['model']); ?></h4>
                                    <span class="px-3 py-1 rounded-full bg-accent/10 border border-accent/20 text-[8px] font-black uppercase tracking-widest text-accent self-center">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">#WAMS-ORD-<?php echo sprintf("%05d", $order['id']); ?></p>
                            </div>
                            <div class="text-center sm:text-right px-6">
                                <p class="text-lg font-black text-foreground tabular-nums mb-1"><?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></p>
                                <p class="text-[8px] font-black text-muted-foreground uppercase"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                            <a href="<?php echo url('customer/orders/view/' . $order['id']); ?>" class="w-12 h-12 rounded-xl bg-muted flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-white transition-all shadow-sm">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Inquiry History -->
        <section>
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-black uppercase tracking-tighter text-foreground flex items-center gap-3">
                    <i class="fas fa-envelope-open-text text-accent"></i>
                    Latest Correspondence
                </h3>
            </div>

            <div class="space-y-4">
                <?php if (empty($inquiries)): ?>
                    <div class="glass p-12 rounded-[3rem] border border-dashed border-border/50 text-center">
                        <i class="fas fa-comment-slash text-4xl text-muted-foreground/30 mb-4"></i>
                        <p class="text-sm font-bold text-muted-foreground italic">You haven't initiated any inquiries yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($inquiries, 0, 2) as $inq): ?>
                        <div class="glass p-8 rounded-[2rem] border border-border/50 shadow-sm hover:shadow-xl transition-all group relative overflow-hidden">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-accent/20 group-hover:bg-accent transition-colors"></div>
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-widest text-accent mb-1"><?php echo $inq['subject'] ?: 'Inquiry'; ?></h4>
                                    <p class="text-[10px] font-bold text-muted-foreground italic"><?php echo date('M d, Y - H:i', strtotime($inq['created_at'])); ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-lg <?php echo $inq['status'] === 'REPLIED' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-500'; ?> text-[9px] font-black uppercase tracking-widest">
                                    <?php echo $inq['status']; ?>
                                </span>
                            </div>
                            <div class="p-5 bg-muted/40 rounded-2xl border border-border/30 mb-4">
                                <p class="text-sm font-medium text-foreground/80 italic leading-relaxed line-clamp-2">"<?php echo clean($inq['message']); ?>"</p>
                            </div>
                            <?php if ($inq['car_id']): ?>
                                <div class="flex items-center gap-3 group/link">
                                    <i class="fas fa-car-side text-[10px] text-muted-foreground"></i>
                                    <a href="<?php echo url('car-detail/' . $inq['slug']); ?>" class="text-[9px] font-black uppercase tracking-widest text-muted-foreground hover:text-accent transition-colors">Related Vehicle: <?php echo $inq['year'] . ' ' . $inq['make'] . ' ' . $inq['model']; ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Right Side: Favorites -->
    <div class="space-y-12 reveal-content">
        
        <!-- Profile Snippet -->
        <section class="glass p-10 rounded-[3rem] border border-border shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <i class="fas fa-user-circle text-8xl"></i>
            </div>
            <h3 class="text-xs font-black uppercase tracking-widest text-muted-foreground mb-8 flex items-center gap-2">
                <i class="fas fa-user-crown text-accent"></i>
                Member Profile
            </h3>
            <div class="space-y-6">
                <div>
                    <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">Authenticated Signature</span>
                    <p class="text-lg font-bold text-foreground"><?php echo clean($user['email']); ?></p>
                </div>
                <div>
                    <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">Direct Line Contact</span>
                    <p class="text-sm font-bold text-foreground"><?php echo $user['phone'] ?: 'No phone index found'; ?></p>
                </div>
                <div class="pt-6 border-t border-border/30">
                    <a href="<?php echo url('logout'); ?>" class="flex items-center gap-3 text-red-500 hover:text-red-600 font-bold uppercase tracking-widest text-[9px] transition-colors">
                        <i class="fas fa-power-off"></i> Terminate Session
                    </a>
                </div>
            </div>
        </section>

        <!-- Saved Favorites -->
        <section>
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-black uppercase tracking-tighter text-foreground flex items-center gap-3">
                    <i class="fas fa-heart text-accent"></i>
                    Wishlist
                </h3>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <?php if (empty($favorites)): ?>
                    <div class="glass p-12 rounded-[3rem] border border-dashed border-border/50 text-center">
                        <i class="fas fa-heart-crack text-4xl text-muted-foreground/30 mb-4"></i>
                        <p class="text-[10px] font-bold text-muted-foreground italic">Wishlist is empty.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($favorites, 0, 4) as $fv): ?>
                        <div class="glass p-5 rounded-[2.5rem] border border-border/50 shadow-md group hover:border-accent transition-all flex items-center gap-5">
                            <div class="w-16 h-16 rounded-2xl overflow-hidden flex-shrink-0 border border-border/30 shadow-inner">
                                <img src="<?php echo url($fv['primary_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="">
                            </div>
                            <div class="flex-1 overflow-hidden">
                                <h4 class="text-sm font-black text-foreground truncate uppercase tracking-tight"><?php echo clean($fv['make_name'] . ' ' . $fv['model']); ?></h4>
                                <p class="text-[9px] font-bold text-accent"><?php echo formatPrice($fv['price'], $fv['price_unit'] ?? null); ?></p>
                            </div>
                            <a href="<?php echo url('car-detail/' . $fv['slug']); ?>" class="w-10 h-10 rounded-xl bg-muted/50 border border-border/50 flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-white transition-all shadow-sm">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'Intelligence Overview');
?>
