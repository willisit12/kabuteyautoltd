<?php
/**
 * pages/customer/orders/view.php
 * Granular Acquisition Tracking
 */
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/layout/customer-layout.php';

requireAuth();
$user = getUserInfo();
$db = getDB();

$order_id = $vars['id'] ?? 0;

$stmt = $db->prepare("
    SELECT o.*, c.make, c.model, c.year, c.slug, c.price as car_price, c.price_unit, c.vin, c.mileage, c.fuel_type, c.transmission, c.color,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as primary_image
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'The requested acquisition record could not be located.');
    redirect(url('customer/orders'));
}

// Status step mapping
$steps = ['PENDING', 'PAID', 'SHIPPED', 'COMPLETED'];
$currentStep = array_search($order['status'], $steps);
if ($currentStep === false) $currentStep = 0;

$stepLabels = [
    ['icon' => 'fa-file-signature', 'label' => 'Order Placed'],
    ['icon' => 'fa-credit-card',    'label' => 'Payment'],
    ['icon' => 'fa-truck-fast',     'label' => 'In Transit'],
    ['icon' => 'fa-handshake',      'label' => 'Delivered'],
];

ob_start();
?>

<div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-5">
        <a href="<?php echo url('customer/orders'); ?>" class="w-11 h-11 rounded-2xl bg-muted/50 border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-2xl font-black text-foreground tracking-tighter uppercase leading-none mb-1">Order <span class="text-gradient">#ORD-<?php echo sprintf("%05d", $order['id']); ?></span></h1>
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Placed <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
        </div>
    </div>
    <?php
    $statusColors = [
        'PENDING'   => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500',
        'PAID'      => 'bg-green-500/10 border-green-500/20 text-green-500',
        'SHIPPED'   => 'bg-blue-500/10 border-blue-500/20 text-blue-500',
        'COMPLETED' => 'bg-green-500 border-transparent text-white',
        'CANCELLED' => 'bg-red-500/10 border-red-500/20 text-red-500',
    ];
    $statusClass = $statusColors[$order['status']] ?? 'bg-muted border-border text-muted-foreground';
    ?>
    <span class="px-5 py-2.5 rounded-full border text-[10px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
        <?php echo $order['status']; ?>
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Progress Stepper -->
        <?php if ($order['status'] !== 'CANCELLED'): ?>
        <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-8">Order Progress</h3>
            <div class="relative flex justify-between items-start">
                <!-- Track line -->
                <div class="absolute left-5 right-5 h-0.5 bg-muted/40 top-5 z-0"></div>
                <div class="absolute left-5 h-0.5 bg-accent top-5 z-0 transition-all duration-700"
                     style="width: calc(<?php echo $currentStep; ?> / 3 * (100% - 2.5rem))"></div>

                <?php foreach ($stepLabels as $i => $step): ?>
                <?php $done = $i <= $currentStep; ?>
                <div class="relative z-10 flex flex-col items-center gap-3 flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs transition-all duration-500
                        <?php echo $done ? 'bg-accent text-white shadow-[0_0_16px_rgba(249,115,22,0.4)]' : 'bg-muted border border-border text-muted-foreground'; ?>">
                        <i class="fas <?php echo $step['icon']; ?>"></i>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-wider text-center leading-tight
                        <?php echo $done ? 'text-foreground' : 'text-muted-foreground opacity-50'; ?>">
                        <?php echo $step['label']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tracking Info (if available) -->
        <?php if (!empty($order['tracking_number']) || !empty($order['tracking_details'])): ?>
        <div class="glass p-8 rounded-[2.5rem] border border-accent/20 bg-accent/[0.02] shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-accent mb-6 flex items-center gap-2">
                <i class="fas fa-truck-fast"></i> Shipping & Tracking
            </h3>
            <?php if (!empty($order['tracking_number'])): ?>
            <div class="mb-4">
                <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground block mb-1">Tracking Number</span>
                <span class="font-mono font-black text-foreground text-sm"><?php echo clean($order['tracking_number']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['tracking_details'])): ?>
            <div class="bg-muted/30 rounded-2xl p-5 border border-border/30">
                <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground block mb-2">Update from Team</span>
                <p class="text-sm font-medium text-foreground leading-relaxed"><?php echo nl2br(clean($order['tracking_details'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Vehicle Card -->
        <div class="glass p-6 rounded-[2.5rem] border border-border/50 shadow-xl overflow-hidden group">
            <div class="flex flex-col sm:flex-row gap-6">
                <div class="w-full sm:w-2/5 aspect-video rounded-2xl overflow-hidden shadow-lg flex-shrink-0">
                    <img src="<?php echo url($order['primary_image'] ?: 'assets/images/placeholder.jpg'); ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                </div>
                <div class="flex-1 flex flex-col justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-foreground tracking-tighter uppercase leading-tight mb-1">
                            <?php echo $order['year'] . ' ' . clean($order['make'] . ' ' . $order['model']); ?>
                        </h2>
                        <p class="text-2xl font-black text-accent tabular-nums"><?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <?php if ($order['vin']): ?>
                        <div class="bg-muted/30 p-3 rounded-xl border border-border/30">
                            <span class="block text-[8px] font-black uppercase text-muted-foreground mb-0.5">VIN</span>
                            <span class="text-[10px] font-bold text-foreground font-mono"><?php echo clean($order['vin']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="bg-muted/30 p-3 rounded-xl border border-border/30">
                            <span class="block text-[8px] font-black uppercase text-muted-foreground mb-0.5">Mileage</span>
                            <span class="text-sm font-black text-foreground"><?php echo number_format((float)$order['mileage']); ?> km</span>
                        </div>
                        <div class="bg-muted/30 p-3 rounded-xl border border-border/30">
                            <span class="block text-[8px] font-black uppercase text-muted-foreground mb-0.5">Fuel</span>
                            <span class="text-sm font-black text-foreground"><?php echo clean($order['fuel_type']); ?></span>
                        </div>
                        <div class="bg-muted/30 p-3 rounded-xl border border-border/30">
                            <span class="block text-[8px] font-black uppercase text-muted-foreground mb-0.5">Transmission</span>
                            <span class="text-sm font-black text-foreground"><?php echo clean($order['transmission']); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo url('car-detail/' . $order['slug']); ?>"
                       class="w-full py-3 rounded-2xl bg-muted border border-border text-foreground font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-2 hover:bg-accent hover:text-white hover:border-accent transition-all">
                        <i class="fas fa-external-link-alt"></i> View Vehicle Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Order Summary -->
        <section class="glass p-7 rounded-[2.5rem] border border-border/50 shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-6">Order Summary</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Reference</span>
                    <span class="text-xs font-black text-foreground">#ORD-<?php echo sprintf("%05d", $order['id']); ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Placed</span>
                    <span class="text-xs font-bold text-foreground"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Payment</span>
                    <span class="text-xs font-bold text-foreground italic"><?php echo clean($order['payment_method']); ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Last Update</span>
                    <span class="text-xs font-bold text-foreground"><?php echo date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'])); ?></span>
                </div>
                <div class="flex justify-between items-center pt-1">
                    <span class="text-[10px] font-black uppercase tracking-widest text-foreground">Total</span>
                    <span class="text-lg font-black text-accent tabular-nums"><?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></span>
                </div>
            </div>
        </section>

        <!-- Contact Support -->
        <section class="glass p-7 rounded-[2.5rem] border border-accent/20 bg-accent/[0.03] shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-accent mb-3">Need Help?</h3>
            <p class="text-[10px] font-medium text-foreground/60 leading-relaxed mb-5">Have a question about your order? Chat with our team directly.</p>
            <a href="<?php echo url('customer/inquiries'); ?>"
               class="flex items-center justify-center gap-2 py-3.5 bg-accent text-white rounded-2xl font-black uppercase tracking-widest text-[9px] shadow-lg hover:scale-[1.02] transition-transform">
                <i class="fas fa-comments"></i> Open Chat
            </a>
        </section>

        <!-- Cancel Order (only if PENDING) -->
        <?php if ($order['status'] === 'PENDING'): ?>
        <section class="glass p-7 rounded-[2.5rem] border border-red-500/20 shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-red-500 mb-3">Cancel Order</h3>
            <p class="text-[10px] font-medium text-foreground/60 leading-relaxed mb-5">You can cancel this order while it's still pending. This will release the vehicle back to inventory.</p>
            <form action="<?php echo url('customer/orders/delete'); ?>" method="POST"
                  onsubmit="return confirm('Cancel this order? The vehicle will be returned to inventory.');">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <button type="submit"
                        class="w-full py-3.5 rounded-2xl border border-red-500/30 bg-red-500/5 text-red-500 font-black uppercase tracking-widest text-[9px] hover:bg-red-500 hover:text-white transition-all">
                    <i class="fas fa-times-circle mr-2"></i> Cancel Order
                </button>
            </form>
        </section>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'Order #' . sprintf("%05d", $order['id']));
?>

if (!$order) {
    setFlash('error', 'The requested acquisition record could not be located.');
    redirect(url('customer/orders'));
}

ob_start();
?>

<div class="mb-12 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-6">
        <a href="<?php echo url('customer/orders'); ?>" class="w-12 h-12 rounded-2xl bg-muted/50 border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase leading-none mb-1">Tracking <span class="text-gradient">#<?php echo sprintf("%05d", $order['id']); ?></span></h1>
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Vehicle Asset Acquisition Progress</p>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <?php
        $statusColors = [
            'PENDING' => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500',
            'PAID' => 'bg-green-500/10 border-green-500/20 text-green-500',
            'SHIPPED' => 'bg-blue-500/10 border-blue-500/20 text-blue-500',
            'COMPLETED' => 'bg-green-500 border-transparent text-white',
            'CANCELLED' => 'bg-red-500/10 border-red-500/20 text-red-500',
        ];
        $statusClass = $statusColors[$order['status']] ?? 'bg-accent/10 border-accent/20 text-accent';
        ?>
        <span class="px-5 py-2 rounded-full border text-[10px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
            Current Status: <?php echo $order['status']; ?>
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Main Tracking Details -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Progress Stepper (Simulated) -->
        <div class="glass p-12 rounded-[3.5rem] border border-border/50 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-10 opacity-5">
                <i class="fas fa-route text-8xl"></i>
            </div>
            
            <div class="relative flex justify-between items-center max-w-2xl mx-auto">
                <!-- Connectors -->
                <div class="absolute left-0 right-0 h-0.5 bg-muted/30 top-1/2 -translate-y-1/2 z-0"></div>
                <div class="absolute left-0 w-1/3 h-0.5 bg-accent top-1/2 -translate-y-1/2 z-0"></div>

                <!-- Step 1 -->
                <div class="relative z-10 flex flex-col items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center shadow-[0_0_20px_rgba(249,115,22,0.5)]">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-foreground">Initiated</span>
                </div>

                <!-- Step 2 -->
                <div class="relative z-10 flex flex-col items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center shadow-[0_0_20px_rgba(249,115,22,0.5)]">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-foreground">Review</span>
                </div>

                <!-- Step 3 -->
                <div class="relative z-10 flex flex-col items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-muted border border-border text-muted-foreground flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-xs"></i>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground opacity-60">Payment</span>
                </div>

                <!-- Step 4 -->
                <div class="relative z-10 flex flex-col items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-muted border border-border text-muted-foreground flex items-center justify-center">
                        <i class="fas fa-handshake text-xs"></i>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground opacity-60">Handover</span>
                </div>
            </div>
        </div>

        <!-- Asset Details Component -->
        <div class="glass p-10 rounded-[3rem] border border-border/50 shadow-xl overflow-hidden group">
            <div class="flex flex-col md:flex-row gap-10">
                <div class="w-full md:w-3/5 aspect-video rounded-3xl overflow-hidden shadow-2xl relative">
                    <img src="<?php echo url($order['primary_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex flex-col justify-end p-8">
                        <h2 class="text-3xl font-black text-white tracking-tighter uppercase leading-none"><?php echo $order['year'] . ' ' . clean($order['make'] . ' ' . $order['model']); ?></h2>
                    </div>
                </div>
                <div class="w-full md:w-2/5 space-y-6 flex flex-col justify-center">
                    <div>
                        <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-accent mb-1">Asset Valuation</span>
                        <p class="text-3xl font-black text-foreground tabular-nums tracking-tighter"><?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-muted/30 p-4 rounded-2xl border border-border/30">
                            <span class="block text-[8px] font-black uppercase text-muted-foreground mb-1">VIN Reference</span>
                            <span class="text-[10px] font-bold text-foreground font-mono"><?php echo $order['vin'] ?: 'Pending'; ?></span>
                        </div>
                        <div class="bg-muted/30 p-4 rounded-2xl border border-border/30">
                            <span class="block text-[8px] font-black uppercase text-muted-foreground mb-1">Index Mileage</span>
                            <span class="text-sm font-black text-foreground tabular-nums"><?php echo number_format((float)$order['mileage']); ?> km</span>
                        </div>
                    </div>
                    <a href="<?php echo url('car-detail/' . $order['slug']); ?>" class="w-full py-5 rounded-2xl bg-foreground text-background font-black uppercase tracking-widest text-[10px] flex items-center justify-center gap-3 hover:bg-accent hover:text-white transition-all">
                        <i class="fas fa-external-link-alt"></i>
                        View Full Blueprint
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-8">
        <!-- Transaction Timeline -->
        <section class="glass p-8 rounded-[3rem] border border-border/50 shadow-xl">
            <h3 class="text-xs font-black uppercase tracking-widest text-muted-foreground mb-8">Protocol Timeline</h3>
            <div class="space-y-8 relative">
                <div class="absolute left-3 top-2 bottom-2 w-[1px] bg-border/50 z-0"></div>
                
                <div class="relative z-10 flex gap-5">
                    <div class="w-6 h-6 rounded-full bg-accent border-4 border-background flex-shrink-0"></div>
                    <div>
                        <p class="text-xs font-black text-foreground uppercase tracking-tight mb-1">Acquisition Protocol Initiated</p>
                        <p class="text-[10px] font-bold text-muted-foreground opacity-60 italic"><?php echo date('M d, Y - H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>

                <div class="relative z-10 flex gap-5">
                    <div class="w-6 h-6 rounded-full bg-muted border-4 border-background flex-shrink-0"></div>
                    <div>
                        <p class="text-xs font-black text-foreground uppercase tracking-tight mb-1 opacity-50">Intelligence Verification</p>
                        <p class="text-[10px] font-bold text-muted-foreground opacity-40">Awaiting clearance...</p>
                    </div>
                </div>

                <div class="relative z-10 flex gap-5">
                    <div class="w-6 h-6 rounded-full bg-muted border-4 border-background flex-shrink-0"></div>
                    <div>
                        <p class="text-xs font-black text-foreground uppercase tracking-tight mb-1 opacity-50">Logistics Deployment</p>
                        <p class="text-[10px] font-bold text-muted-foreground opacity-40 italic">Vehicle handover protocol</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Support Access -->
        <section class="glass p-8 rounded-[3rem] border border-accent/20 bg-accent/5 shadow-xl">
            <h3 class="text-xs font-black uppercase tracking-widest text-accent mb-4">Concierge Priority</h3>
            <p class="text-[10px] font-medium text-foreground/70 leading-relaxed mb-6 italic">Need immediate intelligence regarding this acquisition? Our executive concierge is on standby.</p>
            <a href="<?php echo url('customer/inquiries'); ?>" class="flex items-center justify-center py-4 bg-accent text-white rounded-2xl font-black uppercase tracking-widest text-[9px] shadow-lg shadow-accent/20 hover:scale-[1.02] transition-transform">
                Open Secure Communication
            </a>
        </section>
    </div>
</div>

<?php
$content = ob_get_clean();
renderCustomerLayout($content, 'Tracking #' . sprintf("%05d", $order['id']));
?>
