<?php
/**
 * pages/customer/orders/index.php
 * Acquisition History & Tracking
 */
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/layout/customer-layout.php';

requireAuth();
$user = getUserInfo();
$db = getDB();

// Fetch all orders for this user
$stmt = $db->prepare("
    SELECT o.*, c.make, c.model, c.year, c.slug, c.price as car_price, c.price_unit,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as primary_image
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$success = getFlash('success');
$error = getFlash('error');

ob_start();
?>

<div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
    <div>
        <h1 class="text-4xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">My <span class="text-gradient">Acquisitions.</span></h1>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Full vehicle purchase & tracking history</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="glass rounded-[3rem] border border-border/50 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-muted/30 border-b border-border/50">
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Vehicle Asset</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Reference</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Valuation</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Status</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-muted-foreground text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/10">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <i class="fas fa-shopping-bag text-5xl text-muted-foreground/20 mb-6"></i>
                            <p class="text-sm font-bold text-muted-foreground italic uppercase tracking-widest">No Acquisitions Found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-accent/[0.02] transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-5">
                                <div class="w-16 h-12 rounded-xl overflow-hidden border border-border/30 shadow-inner flex-shrink-0">
                                    <img src="<?php echo url($order['primary_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <p class="font-black text-foreground tracking-tight"><?php echo $order['year'] . ' ' . clean($order['make'] . ' ' . $order['model']); ?></p>
                                    <span class="text-[9px] font-bold text-muted-foreground uppercase"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="text-[10px] font-black tabular-nums text-muted-foreground uppercase opacity-60">#WAMS-ORD-<?php echo sprintf("%05d", $order['id']); ?></span>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-black text-foreground tabular-nums"><?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></p>
                        </td>
                        <td class="px-8 py-6">
                            <?php
                            $statusColors = [
                                'PENDING' => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500',
                                'PAID' => 'bg-green-500/10 border-green-500/20 text-green-500',
                                'SHIPPED' => 'bg-blue-500/10 border-blue-500/20 text-blue-500',
                                'COMPLETED' => 'bg-green-500 border-transparent text-white',
                                'CANCELLED' => 'bg-red-500/10 border-red-500/20 text-red-500',
                            ];
                            $statusClass = $statusColors[$order['status']] ?? 'bg-muted border-border text-muted-foreground';
                            ?>
                            <span class="px-3 py-1 rounded-full border text-[8px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="<?php echo url('customer/orders/view/' . $order['id']); ?>" 
                                   class="w-10 h-10 rounded-xl bg-muted/50 flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all shadow-sm"
                                   title="Track Progress">
                                    <i class="fas fa-location-arrow text-xs"></i>
                                </a>
                                <form action="<?php echo url('customer/orders/delete'); ?>" method="POST" onsubmit="return confirm('Note: Deleting an order request is permanent. Proceed?');" class="inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <button type="submit" class="w-10 h-10 rounded-xl bg-muted/50 flex items-center justify-center text-muted-foreground hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'My Acquisitions');
?>
