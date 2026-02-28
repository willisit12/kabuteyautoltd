<?php
/**
 * admin/orders/create.php
 * Administrative Manual Order Initiation
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

requireAdmin();

$db = getDB();

// Handle Order Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $car_id = intval($_POST['car_id'] ?? 0);
    $status = $_POST['status'] ?? 'PENDING';
    $payment_method = clean($_POST['payment_method'] ?? 'Administrative Settlement');
    
    // Fetch car for price and status check
    $stmt = $db->prepare("SELECT price, status FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    
    if (!$car || !$user_id) {
        setFlash('error', 'Intelligence mismatch. Verify customer and asset selection.');
    } else {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO orders (user_id, car_id, amount, status, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $car_id, $car['price'], $status, $payment_method]);
            $order_id = $db->lastInsertId();
            
            // Update car status
            $stmt = $db->prepare("UPDATE cars SET status = 'RESERVED' WHERE id = ?");
            $stmt->execute([$car_id]);
            
            $db->commit();
            
            createNotification($user_id, "Order Initialized: #ORD-" . str_pad((string)$order_id, 5, '0', STR_PAD_LEFT), "An administrative order has been initiated for you. Acquisition status: " . $status, 'SUCCESS', 'dashboard');
            
            setFlash('success', 'Acquisition protocol initialized manually.');
            redirect(url('admin/orders/view.php?id=' . $order_id));
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            setFlash('error', 'Execution failure: ' . $e->getMessage());
        }
    }
}

// Fetch Users for selection
$stmt = $db->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name ASC");
$customers = $stmt->fetchAll();

// Fetch Available Cars
$stmt = $db->query("SELECT id, make, model, year, price FROM cars WHERE status = 'AVAILABLE' ORDER BY make, model ASC");
$availableCars = $stmt->fetchAll();

$error = getFlash('error');

ob_start();
?>

<div class="mb-12">
    <div class="flex items-center gap-4 mb-4">
        <a href="<?php echo url('admin/orders'); ?>" class="w-10 h-10 rounded-full bg-muted border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase">Manual <span class="text-gradient">Acquisition.</span></h1>
    </div>
    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60 ml-14">Initiate a formal transaction record on behalf of a customer</p>
</div>

<?php if ($error): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-12 flex items-center gap-4 text-sm font-bold">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="max-w-4xl">
    <div class="glass p-12 rounded-[4rem] border border-border/50 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-[0.03] pointer-events-none">
            <i class="fas fa-file-invoice-dollar text-[15rem]"></i>
        </div>

        <form method="POST" class="space-y-10 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Select Customer</label>
                    <select name="user_id" required class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                        <option value="">Choose Intellectual Node...</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo clean($c['name']); ?> (<?php echo clean($c['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="space-y-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Select Asset</label>
                    <select name="car_id" required class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                        <option value="">Choose Available Asset...</option>
                        <?php foreach ($availableCars as $car): ?>
                            <option value="<?php echo $car['id']; ?>"><?php echo $car['year'] . ' ' . $car['make'] . ' ' . $car['model']; ?> - <?php echo formatPrice($car['price']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Initial Status</label>
                    <select name="status" class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none appearance-none">
                        <option value="PENDING">PENDING</option>
                        <option value="PAID">PAID</option>
                        <option value="SHIPPED">SHIPPED</option>
                    </select>
                </div>
                <div class="space-y-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Settlement Method</label>
                    <input type="text" name="payment_method" value="Administrative Settlement"
                           class="w-full bg-background/50 border border-border text-foreground px-6 py-5 rounded-2xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold outline-none">
                </div>
            </div>

            <div class="pt-8 flex items-center gap-6">
                <button type="submit" class="inline-flex items-center gap-4 px-12 py-6 bg-accent text-white rounded-[2rem] font-black uppercase tracking-widest text-[11px] shadow-[0_15px_40px_rgba(249,115,22,0.3)] hover:scale-[1.03] active:scale-[0.98] transition-all group">
                    Execute Initiation
                    <i class="fas fa-rocket text-sm group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
                </button>
                <p class="text-[9px] font-bold text-muted-foreground italic max-w-xs leading-relaxed">Performing this action will automatically reserve the asset and notify the selected customer intelligence node.</p>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Manual Initiation');
?>
