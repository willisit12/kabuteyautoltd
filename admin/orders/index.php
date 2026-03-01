<?php
/**
 * admin/orders/index.php
 * Administrative Order Management Dashboard
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

requireAdmin();

$db = getDB();
$status_filter = $_GET['status'] ?? 'ALL';

$query = "
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           c.make, c.model, c.year, c.slug, c.price as car_price, c.price_unit
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
";

if ($status_filter !== 'ALL') {
    $query .= " WHERE o.status = " . $db->quote($status_filter);
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->query($query);
$orders = $stmt->fetchAll();

$stats = [
    'total' => count($orders),
    'pending' => 0,
    'shipped' => 0,
    'completed' => 0
];

foreach ($orders as $o) {
    if ($o['status'] === 'PENDING') $stats['pending']++;
    if ($o['status'] === 'SHIPPED') $stats['shipped']++;
    if ($o['status'] === 'COMPLETED') $stats['completed']++;
}

ob_start();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
    <div>
        <h1 class="text-4xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">Acquisition <span class="text-gradient">Portfolio.</span></h1>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Executive management of vehicle transactions</p>
    </div>
    
    <div class="flex gap-4">
        <a href="<?php echo url('admin/orders/create.php'); ?>" class="btn-premium bg-accent text-white px-8 py-4 rounded-2xl font-black uppercase tracking-tighter text-[10px] shadow-lg hover:scale-105 transition-all flex items-center gap-3">
            <i class="fas fa-plus"></i> Manual Order
        </a>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
    <div class="glass p-8 rounded-[2.5rem] border border-border/50">
        <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-muted-foreground mb-4">Total Volume</span>
        <div class="flex items-end justify-between">
            <span class="text-3xl font-black text-foreground tracking-tighter"><?php echo $stats['total']; ?></span>
            <i class="fas fa-layer-group text-accent/20 text-2xl"></i>
        </div>
    </div>
    <div class="glass p-8 rounded-[2.5rem] border border-border/50">
        <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-yellow-500 mb-4">Pending Review</span>
        <div class="flex items-end justify-between">
            <span class="text-3xl font-black text-foreground tracking-tighter"><?php echo $stats['pending']; ?></span>
            <i class="fas fa-clock text-yellow-500/20 text-2xl"></i>
        </div>
    </div>
    <div class="glass p-8 rounded-[2.5rem] border border-border/50">
        <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-blue-500 mb-4">In Transit</span>
        <div class="flex items-end justify-between">
            <span class="text-3xl font-black text-foreground tracking-tighter"><?php echo $stats['shipped']; ?></span>
            <i class="fas fa-truck-fast text-blue-500/20 text-2xl"></i>
        </div>
    </div>
    <div class="glass p-8 rounded-[2.5rem] border border-border/50">
        <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-green-500 mb-4">Finalized</span>
        <div class="flex items-end justify-between">
            <span class="text-3xl font-black text-foreground tracking-tighter"><?php echo $stats['completed']; ?></span>
            <i class="fas fa-check-double text-green-500/20 text-2xl"></i>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="flex flex-wrap gap-3 mb-8">
    <?php foreach(['ALL', 'PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED'] as $s): ?>
        <a href="?status=<?php echo $s; ?>" 
           class="px-5 py-2.5 rounded-full border text-[9px] font-black uppercase tracking-widest transition-all <?php echo $status_filter === $s ? 'bg-foreground text-background border-foreground shadow-lg' : 'bg-muted/30 border-border/50 text-muted-foreground hover:border-accent hover:text-accent'; ?>">
            <?php echo $s; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="glass rounded-[3rem] border border-border/50 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-muted/50 border-b border-border/50">
                    <th class="p-8 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Reference</th>
                    <th class="p-8 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Intelligence (Customer)</th>
                    <th class="p-8 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Asset</th>
                    <th class="p-8 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Valuation</th>
                    <th class="p-8 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Status</th>
                    <th class="p-8 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/30">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="p-20 text-center">
                            <div class="opacity-20 mb-4"><i class="fas fa-folder-open text-5xl"></i></div>
                            <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest leading-loose">No acquisition records found under detected parameters.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-accent/[0.02] transition-colors group">
                            <td class="p-8">
                                <span class="text-xs font-black text-foreground tracking-tighter">#ORD-<?php echo str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                <span class="block text-[8px] font-bold text-muted-foreground mt-1"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></span>
                            </td>
                            <td class="p-8">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent font-black text-xs">
                                        <?php echo substr($o['customer_name'] ?? 'C', 0, 1); ?>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-black text-foreground leading-none"><?php echo clean($o['customer_name']); ?></span>
                                        <span class="text-[9px] font-bold text-muted-foreground mt-1"><?php echo clean($o['customer_email']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-8">
                                <span class="text-xs font-black text-foreground uppercase tracking-tighter"><?php echo $o['year'] . ' ' . $o['make'] . ' ' . $o['model']; ?></span>
                                <a href="<?php echo url('car-detail/' . $o['slug']); ?>" target="_blank" class="block text-[8px] font-bold text-accent mt-1 hover:underline">View Asset <i class="fas fa-external-link-alt ml-1"></i></a>
                            </td>
                            <td class="p-8">
                                <span class="text-xs font-black text-foreground tabular-nums tracking-tighter"><?php echo formatPrice($o['amount'], $o['price_unit'] ?? null); ?></span>
                                <span class="block text-[8px] font-bold text-muted-foreground mt-1 italic"><?php echo $o['payment_method']; ?></span>
                            </td>
                            <td class="p-8">
                                <?php 
                                $status_classes = [
                                    'PENDING' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                                    'PAID' => 'bg-green-500/10 text-green-500 border-green-500/20',
                                    'SHIPPED' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                                    'COMPLETED' => 'bg-green-500 text-white border-transparent',
                                    'CANCELLED' => 'bg-red-500/10 text-red-500 border-red-500/20',
                                ];
                                $cls = $status_classes[$o['status']] ?? 'bg-muted text-muted-foreground';
                                ?>
                                <span class="px-3 py-1 rounded-full border text-[8px] font-black uppercase tracking-widest <?php echo $cls; ?>">
                                    <?php echo $o['status']; ?>
                                </span>
                            </td>
                            <td class="p-8">
                                <a href="<?php echo url('admin/orders/view.php?id=' . $o['id']); ?>" class="w-10 h-10 rounded-xl bg-muted border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white hover:border-accent transition-all shadow-sm">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
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
renderAdminLayout($content, 'Orders Management');
?>
