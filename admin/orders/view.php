<?php
/**
 * admin/orders/view.php
 * Administrative Order Detail & Tracking Management
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if (!$id) redirect(url('admin/orders'));

// Handle Status/Tracking Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'PENDING';
    $tracking_number = clean($_POST['tracking_number'] ?? '');
    $tracking_details = clean($_POST['tracking_details'] ?? '');
    
    $stmt = $db->prepare("UPDATE orders SET status = ?, tracking_number = ?, tracking_details = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $tracking_number, $tracking_details, $id]);
    
    // Fetch order/user for notification
    $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $msg = "Your acquisition status for the vehicle has been updated to: " . $status;
        if ($tracking_number) $msg .= ". Tracking Number: " . $tracking_number;
        createNotification($order['user_id'], "Order Update: #ORD-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT), $msg, 'INFO', 'dashboard');
    }
    
    setFlash('success', 'Acquisition record updated and customer notified.');
    redirect(url('admin/orders/view.php?id=' . $id));
}

$stmt = $db->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address as customer_address,
           c.make, c.model, c.year, c.slug, c.price as car_price, c.status as car_current_status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) redirect(url('admin/orders'));

$success = getFlash('success');

ob_start();
?>

<div class="mb-12 flex items-center justify-between">
    <div>
        <div class="flex items-center gap-4 mb-4">
            <a href="<?php echo url('admin/orders'); ?>" class="w-10 h-10 rounded-full bg-muted border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase">Acquisition <span class="text-gradient">#ORD-<?php echo str_pad((string)$id, 5, '0', STR_PAD_LEFT); ?></span></h1>
        </div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60 ml-14">Detailed intelligence and logistics management</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-12 flex items-center gap-4 text-sm font-bold reveal-content">
        <i class="fas fa-check-circle text-lg"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-12">
        <!-- Asset & Customer Info -->
        <div class="glass p-12 rounded-[4rem] border border-border/50 shadow-2xl relative overflow-hidden">
            <div class="flex flex-col md:flex-row gap-12">
                <div class="flex-1 space-y-8">
                    <section>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-accent mb-6 flex items-center gap-2">
                            <i class="fas fa-car-side"></i> Asset Intelligence
                        </h3>
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-2xl font-black text-foreground uppercase tracking-tighter leading-none mb-2"><?php echo $order['year'] . ' ' . clean($order['make'] . ' ' . $order['model']); ?></h2>
                                <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest italic">Acquisition Valuation: <?php echo formatPrice($order['amount']); ?></p>
                            </div>
                            <span class="px-3 py-1 bg-accent/10 border border-accent/20 rounded-full text-[8px] font-black text-accent uppercase tracking-widest"><?php echo $order['car_current_status']; ?></span>
                        </div>
                    </section>
                    
                    <section class="pt-8 border-t border-border/30">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-accent mb-6 flex items-center gap-2">
                            <i class="fas fa-user-tie"></i> Customer Intelligence
                        </h3>
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <span class="block text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1">Identity</span>
                                <span class="text-sm font-bold text-foreground"><?php echo clean($order['customer_name']); ?></span>
                            </div>
                            <div>
                                <span class="block text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1">Authenticated Index</span>
                                <span class="text-sm font-bold text-foreground"><?php echo clean($order['customer_email']); ?></span>
                            </div>
                            <div>
                                <span class="block text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1">Direct Line</span>
                                <span class="text-sm font-bold text-foreground"><?php echo clean($order['customer_phone'] ?: 'No record'); ?></span>
                            </div>
                            <div>
                                <span class="block text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1">Deployment Node</span>
                                <span class="text-sm font-bold text-foreground"><?php echo clean($order['customer_address'] ?: 'No record'); ?></span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Tracking & Logistics Update Form -->
        <div class="glass p-12 rounded-[4rem] border border-border/50 shadow-2xl relative overflow-hidden bg-accent/[0.02]">
             <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-foreground mb-10 flex items-center gap-2">
                <i class="fas fa-truck-ramp-box"></i> Logistics & Status Management
            </h3>
            
            <form method="POST" class="space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Current Protocol Status</label>
                        <select name="status" class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                            <?php foreach(['PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Global Tracking Number</label>
                        <input type="text" name="tracking_number" value="<?php echo clean($order['tracking_number']); ?>" placeholder="e.g. DHL-EX-998822"
                               class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Logistics Intelligence (Details)</label>
                    <textarea name="tracking_details" placeholder="Provide detailed tracking updates for the customer..."
                              class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none min-h-[150px]"><?php echo clean($order['tracking_details']); ?></textarea>
                </div>

                <div class="pt-6">
                    <button type="submit" class="inline-flex items-center gap-4 px-12 py-6 bg-accent text-white rounded-[2rem] font-black uppercase tracking-widest text-[11px] shadow-[0_15px_40px_rgba(249,115,22,0.3)] hover:scale-[1.03] active:scale-[0.98] transition-all group">
                        Apply Protocol Updates
                        <i class="fas fa-save text-sm group-hover:rotate-12 transition-transform"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-8">
        <section class="glass p-10 rounded-[3rem] border border-border shadow-xl">
             <h3 class="text-xs font-black uppercase tracking-widest text-muted-foreground mb-8">Protocol Metrics</h3>
             <div class="space-y-8">
                <div class="flex items-center justify-between border-b border-border/30 pb-4">
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Initiated</span>
                    <span class="text-xs font-black text-foreground tabular-nums"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="flex items-center justify-between border-b border-border/30 pb-4">
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Last Update</span>
                    <span class="text-xs font-black text-foreground tabular-nums"><?php echo date('M d, H:i', strtotime($order['updated_at'])); ?></span>
                </div>
                <div class="flex items-center justify-between border-b border-border/30 pb-4">
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Settlement</span>
                    <span class="text-xs font-black text-foreground italic"><?php echo $order['payment_method']; ?></span>
                </div>
             </div>
        </section>

        <!-- Rapid Actions -->
        <section class="glass p-10 rounded-[3rem] border border-border shadow-xl bg-accent/[0.02]">
            <h3 class="text-xs font-black uppercase tracking-widest text-accent mb-8">Intelligence Actions</h3>
            <div class="space-y-4">
                <a href="mailto:<?php echo $order['customer_email']; ?>" class="w-full p-5 rounded-2xl bg-background border border-border text-foreground font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-accent hover:text-white transition-all shadow-sm">
                    <i class="fas fa-paper-plane"></i> Direct Dispatch (Email)
                </a>
                <button class="w-full p-5 rounded-2xl bg-background border border-border text-foreground font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-red-500 hover:text-white transition-all shadow-sm opacity-50 cursor-not-allowed">
                    <i class="fas fa-ban"></i> Void Acquisition
                </button>
            </div>
        </section>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Order Analysis');
?>
