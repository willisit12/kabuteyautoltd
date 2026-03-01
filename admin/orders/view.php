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
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security validation failed.');
        redirect(url('admin/orders/view.php?id=' . $id));
    }

    $status          = $_POST['status'] ?? 'PENDING';
    $tracking_number = clean($_POST['tracking_number'] ?? '');
    $tracking_details = clean($_POST['tracking_details'] ?? '');
    $payment_method  = clean($_POST['payment_method'] ?? '');

    $stmt = $db->prepare("UPDATE orders SET status = ?, tracking_number = ?, tracking_details = ?, payment_method = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $tracking_number, $tracking_details, $payment_method, $id]);

    // If cancelled, release car back to available
    if ($status === 'CANCELLED') {
        $stmt = $db->prepare("SELECT car_id FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $db->prepare("UPDATE cars SET status = 'AVAILABLE' WHERE id = ?")->execute([$row['car_id']]);
        }
    }

    // If completed, mark car as SOLD
    if ($status === 'COMPLETED') {
        $stmt = $db->prepare("SELECT car_id FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $db->prepare("UPDATE cars SET status = 'SOLD' WHERE id = ?")->execute([$row['car_id']]);
        }
    }

    // Notify customer
    $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if ($order) {
        $statusMessages = [
            'PENDING'   => 'Your order is under review.',
            'PAID'      => 'Payment confirmed. Your order is being processed.',
            'SHIPPED'   => 'Your vehicle is on its way!' . ($tracking_number ? ' Tracking: ' . $tracking_number : ''),
            'COMPLETED' => 'Your order is complete. Thank you for your purchase!',
            'CANCELLED' => 'Your order has been cancelled. Contact us for more information.',
        ];
        $msg = $statusMessages[$status] ?? "Your order status has been updated to: $status";
        if ($tracking_details) $msg .= ' — ' . $tracking_details;

        createNotification(
            $order['user_id'],
            "Order Update: #ORD-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT),
            $msg,
            $status === 'CANCELLED' ? 'WARNING' : 'INFO',
            'customer/orders/view/' . $id
        );
    }

    setFlash('success', 'Order updated and customer notified.');
    redirect(url('admin/orders/view.php?id=' . $id));
}

$stmt = $db->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address as customer_address,
           c.make, c.model, c.year, c.slug, c.price as car_price, c.price_unit, c.status as car_current_status, c.color, c.fuel_type, c.mileage,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as car_image
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) redirect(url('admin/orders'));

$success = getFlash('success');
$error   = getFlash('error');

$steps = ['PENDING', 'PAID', 'SHIPPED', 'COMPLETED'];
$currentStep = array_search($order['status'], $steps);
if ($currentStep === false) $currentStep = 0;

$statusColors = [
    'PENDING'   => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500',
    'PAID'      => 'bg-green-500/10 border-green-500/20 text-green-500',
    'SHIPPED'   => 'bg-blue-500/10 border-blue-500/20 text-blue-500',
    'COMPLETED' => 'bg-green-500 border-transparent text-white',
    'CANCELLED' => 'bg-red-500/10 border-red-500/20 text-red-500',
];
$statusClass = $statusColors[$order['status']] ?? 'bg-muted border-border text-muted-foreground';

ob_start();
?>

<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('admin/orders'); ?>" class="w-10 h-10 rounded-full bg-muted border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-2xl font-black text-foreground tracking-tighter uppercase">Order <span class="text-gradient">#ORD-<?php echo str_pad((string)$id, 5, '0', STR_PAD_LEFT); ?></span></h1>
            <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground opacity-60">Placed <?php echo date('M d, Y · H:i', strtotime($order['created_at'])); ?></p>
        </div>
    </div>
    <span class="px-4 py-2 rounded-full border text-[9px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
        <?php echo $order['status']; ?>
    </span>
</div>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-5 rounded-2xl mb-6 flex items-center gap-3 text-sm font-bold">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-5 rounded-2xl mb-6 flex items-center gap-3 text-sm font-bold">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Main -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Progress Stepper -->
        <?php if ($order['status'] !== 'CANCELLED'): ?>
        <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-8">Order Progress</h3>
            <div class="relative flex justify-between items-start">
                <div class="absolute left-5 right-5 h-0.5 bg-muted/40 top-5 z-0"></div>
                <div class="absolute left-5 h-0.5 bg-accent top-5 z-0 transition-all duration-700"
                     style="width: calc(<?php echo $currentStep; ?> / 3 * (100% - 2.5rem))"></div>
                <?php
                $stepDefs = [
                    ['icon' => 'fa-file-signature', 'label' => 'Order Placed'],
                    ['icon' => 'fa-credit-card',    'label' => 'Payment'],
                    ['icon' => 'fa-truck-fast',     'label' => 'In Transit'],
                    ['icon' => 'fa-handshake',      'label' => 'Delivered'],
                ];
                foreach ($stepDefs as $i => $step):
                    $done = $i <= $currentStep;
                ?>
                <div class="relative z-10 flex flex-col items-center gap-3 flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs transition-all
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

        <!-- Customer & Vehicle Info -->
        <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Customer -->
                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-accent mb-5 flex items-center gap-2">
                        <i class="fas fa-user-tie"></i> Customer
                    </h3>
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center text-accent font-black text-lg">
                            <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-black text-foreground"><?php echo clean($order['customer_name']); ?></p>
                            <p class="text-[10px] text-muted-foreground"><?php echo clean($order['customer_email']); ?></p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <?php if ($order['customer_phone']): ?>
                        <p class="text-[10px] text-muted-foreground"><i class="fas fa-phone w-4 mr-1 opacity-60"></i><?php echo clean($order['customer_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($order['customer_address']): ?>
                        <p class="text-[10px] text-muted-foreground"><i class="fas fa-map-marker-alt w-4 mr-1 opacity-60"></i><?php echo clean($order['customer_address']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vehicle -->
                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-accent mb-5 flex items-center gap-2">
                        <i class="fas fa-car-side"></i> Vehicle
                    </h3>
                    <?php if ($order['car_image']): ?>
                    <div class="aspect-video rounded-2xl overflow-hidden mb-4 border border-border/30">
                        <img src="<?php echo url($order['car_image']); ?>" class="w-full h-full object-cover">
                    </div>
                    <?php endif; ?>
                    <p class="font-black text-foreground uppercase tracking-tight"><?php echo $order['year'] . ' ' . $order['make'] . ' ' . $order['model']; ?></p>
                    <p class="text-[10px] text-muted-foreground mt-1"><?php echo clean($order['color'] ?? ''); ?> · <?php echo clean($order['fuel_type'] ?? ''); ?> · <?php echo number_format((float)$order['mileage']); ?> km</p>
                    <a href="<?php echo url('car-detail/' . $order['slug']); ?>" target="_blank"
                       class="inline-flex items-center gap-1.5 text-[9px] font-black text-accent uppercase tracking-widest mt-2 hover:underline">
                        View Listing <i class="fas fa-external-link-alt text-[8px]"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Update Form -->
        <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-foreground mb-8 flex items-center gap-2">
                <i class="fas fa-sliders-h text-accent"></i> Update Order
            </h3>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">Status</label>
                        <select name="status" class="w-full bg-background/50 border border-border text-foreground px-5 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                            <?php foreach(['PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">Tracking Number</label>
                        <input type="text" name="tracking_number" value="<?php echo clean($order['tracking_number'] ?? ''); ?>"
                               placeholder="e.g. DHL-EX-998822"
                               class="w-full bg-background/50 border border-border text-foreground px-5 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">Payment Method</label>
                        <input type="text" name="payment_method" value="<?php echo clean($order['payment_method'] ?? ''); ?>"
                               placeholder="e.g. Bank Wire, Cash, Financing"
                               class="w-full bg-background/50 border border-border text-foreground px-5 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">Update Note for Customer</label>
                        <textarea name="tracking_details" rows="3"
                                  placeholder="Provide a status update visible to the customer..."
                                  class="w-full bg-background/50 border border-border text-foreground px-5 py-4 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none resize-none"><?php echo clean($order['tracking_details'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" class="inline-flex items-center gap-3 px-10 py-4 bg-accent text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-[0_10px_30px_rgba(249,115,22,0.3)] hover:scale-[1.03] active:scale-[0.98] transition-all">
                        Save & Notify Customer <i class="fas fa-bell text-sm"></i>
                    </button>
                    <a href="mailto:<?php echo $order['customer_email']; ?>"
                       class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl border border-border bg-muted/30 text-foreground font-black uppercase tracking-widest text-[10px] hover:bg-accent hover:text-white hover:border-accent transition-all">
                        <i class="fas fa-envelope text-sm"></i> Email Customer
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Sidebar -->
    <div class="space-y-6">
        <!-- Order Metrics -->
        <section class="glass p-7 rounded-[2.5rem] border border-border shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-6">Order Details</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Reference</span>
                    <span class="text-xs font-black text-foreground">#ORD-<?php echo str_pad((string)$id, 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Amount</span>
                    <span class="text-sm font-black text-accent"><?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Payment</span>
                    <span class="text-[10px] font-bold text-foreground italic"><?php echo clean($order['payment_method']); ?></span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-border/30">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Created</span>
                    <span class="text-[10px] font-bold text-foreground"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Updated</span>
                    <span class="text-[10px] font-bold text-foreground"><?php echo date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'])); ?></span>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="glass p-7 rounded-[2.5rem] border border-border shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-5">Quick Actions</h3>
            <div class="space-y-3">
                <a href="<?php echo url('admin/inquiries/chat.php?user_id=' . $order['user_id']); ?>"
                   class="w-full p-4 rounded-2xl bg-background border border-border text-foreground font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-2 hover:bg-accent hover:text-white hover:border-accent transition-all">
                    <i class="fas fa-comments"></i> Chat with Customer
                </a>
                <a href="<?php echo url('admin/cars/edit.php?id=' . $order['car_id']); ?>"
                   class="w-full p-4 rounded-2xl bg-background border border-border text-foreground font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-2 hover:bg-foreground hover:text-background transition-all">
                    <i class="fas fa-car"></i> Edit Vehicle
                </a>
            </div>
        </section>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Order #' . str_pad((string)$id, 5, '0', STR_PAD_LEFT));
?>
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
           c.make, c.model, c.year, c.slug, c.price as car_price, c.price_unit, c.status as car_current_status
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
                                <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest italic">Acquisition Valuation: <?php echo formatPrice($order['amount'], $order['price_unit'] ?? null); ?></p>
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
