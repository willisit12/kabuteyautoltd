<?php
/**
 * admin/inquiries/chat.php
 * Administrative Threaded Chat Interface
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

requireAdmin();

$db   = getDB();
$id   = intval($_GET['id'] ?? 0);
$admin = getUserInfo();

if (!$id) redirect(url('admin/inquiries'));

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security validation failed.');
        redirect(url('admin/inquiries/chat.php?id=' . $id));
    }

    $message = clean($_POST['message']);

    if (sendInquiryMessage($id, $admin['id'], $message)) {
        // Notify Customer
        $stmt = $db->prepare("SELECT user_id, subject FROM inquiries WHERE id = ?");
        $stmt->execute([$id]);
        $inq = $stmt->fetch();
        if ($inq && $inq['user_id']) {
            createNotification(
                $inq['user_id'],
                "New Reply: " . ($inq['subject'] ?: 'Vehicle Inquiry'),
                "The team has responded to your inquiry. Tap to view.",
                'SUCCESS',
                'customer/inquiries?id=' . $id
            );
        }
    } else {
        setFlash('error', 'Message dispatch failure.');
    }
    redirect(url('admin/inquiries/chat.php?id=' . $id));
}

// Fetch Inquiry Details
$stmt = $db->prepare("
    SELECT i.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           c.make, c.model, c.year, c.slug,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as car_image
    FROM inquiries i
    LEFT JOIN users u ON i.user_id = u.id
    LEFT JOIN cars c ON i.car_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inquiry = $stmt->fetch();

if (!$inquiry) redirect(url('admin/inquiries'));

$messages = getInquiryMessages($id);
$error    = getFlash('error');

ob_start();
?>

<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('admin/inquiries'); ?>"
           class="w-10 h-10 rounded-full bg-muted border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-2xl font-black text-foreground tracking-tighter uppercase leading-none">
                Chat — <span class="text-gradient"><?php echo clean($inquiry['customer_name'] ?? $inquiry['name']); ?></span>
            </h1>
            <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground opacity-60 mt-0.5">
                <?php echo clean($inquiry['subject'] ?: 'Vehicle Inquiry'); ?>
                <?php if ($inquiry['car_id']): ?>
                    · <?php echo $inquiry['year'] . ' ' . $inquiry['make'] . ' ' . $inquiry['model']; ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php
    $statusColors = ['PENDING' => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500', 'REPLIED' => 'bg-green-500/10 border-green-500/20 text-green-500', 'ARCHIVED' => 'bg-muted border-border text-muted-foreground'];
    $sc = $statusColors[$inquiry['status']] ?? 'bg-muted border-border text-muted-foreground';
    ?>
    <span class="px-4 py-1.5 rounded-full border text-[9px] font-black uppercase tracking-widest <?php echo $sc; ?>">
        <?php echo $inquiry['status']; ?>
    </span>
</div>

<?php if ($error): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-6 text-sm font-bold flex items-center gap-3">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Chat Area -->
    <div class="lg:col-span-3">
        <div class="glass rounded-[2.5rem] border border-border/50 shadow-2xl flex flex-col overflow-hidden" style="height: 680px;">
            <!-- Chat Header -->
            <div class="px-8 py-5 border-b border-border/30 bg-muted/20 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-accent/10 flex items-center justify-center text-accent font-black text-sm">
                        <?php echo strtoupper(substr($inquiry['customer_name'] ?? $inquiry['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-foreground leading-none"><?php echo clean($inquiry['customer_name'] ?? $inquiry['name']); ?></span>
                        <span class="text-[9px] font-bold text-muted-foreground"><?php echo clean($inquiry['email']); ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[9px] font-bold text-muted-foreground"><?php echo count($messages); ?> messages</span>
                    <!-- Archive toggle -->
                    <form method="POST" action="<?php echo url('admin/inquiries/view.php?id=' . $id); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="status" value="<?php echo $inquiry['status'] === 'ARCHIVED' ? 'PENDING' : 'ARCHIVED'; ?>">
                        <button type="submit" class="px-3 py-1.5 rounded-xl border border-border text-[9px] font-black uppercase tracking-widest text-muted-foreground hover:border-accent hover:text-accent transition-all">
                            <?php echo $inquiry['status'] === 'ARCHIVED' ? 'Unarchive' : 'Archive'; ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-8 space-y-6" id="message-container">
                <?php if (empty($messages)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-center opacity-40">
                        <i class="fas fa-comments text-4xl mb-3 text-muted-foreground"></i>
                        <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest">No messages yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg):
                        $isAdmin = ($msg['sender_role'] === 'admin');
                    ?>
                    <div class="flex <?php echo $isAdmin ? 'justify-end' : 'justify-start'; ?> gap-3">
                        <?php if (!$isAdmin): ?>
                        <div class="w-8 h-8 rounded-full bg-muted border border-border flex items-center justify-center text-xs font-black text-muted-foreground flex-shrink-0 mt-1">
                            <?php echo strtoupper(substr($msg['sender_name'] ?? 'C', 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <div class="max-w-[72%] flex flex-col gap-1.5 <?php echo $isAdmin ? 'items-end' : 'items-start'; ?>">
                            <div class="px-5 py-3.5 rounded-2xl text-sm font-medium leading-relaxed
                                <?php echo $isAdmin
                                    ? 'bg-foreground text-background rounded-tr-sm'
                                    : 'glass border border-border/50 text-foreground rounded-tl-sm'; ?>">
                                <?php echo nl2br(clean($msg['message'])); ?>
                            </div>
                            <span class="text-[8px] font-bold text-muted-foreground opacity-60 px-1">
                                <?php echo $isAdmin ? 'You (Admin)' : clean($msg['sender_name'] ?? 'Customer'); ?>
                                · <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                            </span>
                        </div>
                        <?php if ($isAdmin): ?>
                        <div class="w-8 h-8 rounded-full bg-accent/10 border border-accent/20 flex items-center justify-center text-xs font-black text-accent flex-shrink-0 mt-1">
                            <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input -->
            <div class="px-6 py-5 border-t border-border/30 bg-muted/10 flex-shrink-0">
                <form method="POST" class="flex items-end gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <textarea
                        name="message"
                        id="admin-msg-input"
                        placeholder="Type your reply..."
                        required
                        rows="2"
                        class="flex-1 bg-background border border-border rounded-2xl px-5 py-3.5 text-sm font-medium focus:ring-2 focus:ring-accent outline-none resize-none transition-all"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
                    ></textarea>
                    <button type="submit"
                            class="w-12 h-12 rounded-2xl bg-accent text-white flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition-all flex-shrink-0">
                        <i class="fas fa-paper-plane text-sm"></i>
                    </button>
                </form>
                <p class="text-[9px] text-muted-foreground mt-2 ml-1">Enter to send · Shift+Enter for new line</p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Vehicle -->
        <?php if ($inquiry['car_id']): ?>
        <section class="glass p-6 rounded-[2rem] border border-border shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-5">Vehicle</h3>
            <?php if ($inquiry['car_image']): ?>
            <div class="aspect-video rounded-xl overflow-hidden mb-4 border border-border/30">
                <img src="<?php echo url($inquiry['car_image']); ?>" class="w-full h-full object-cover">
            </div>
            <?php endif; ?>
            <p class="font-black text-foreground text-sm uppercase tracking-tight"><?php echo $inquiry['year'] . ' ' . $inquiry['make'] . ' ' . $inquiry['model']; ?></p>
            <div class="flex gap-2 mt-3">
                <a href="<?php echo url('car-detail/' . $inquiry['slug']); ?>" target="_blank"
                   class="flex-1 py-2.5 rounded-xl border border-border text-[9px] font-black uppercase tracking-widest text-foreground text-center hover:bg-accent hover:text-white hover:border-accent transition-all">
                    View
                </a>
                <a href="<?php echo url('admin/cars/edit.php?id=' . $inquiry['car_id']); ?>"
                   class="flex-1 py-2.5 rounded-xl border border-border text-[9px] font-black uppercase tracking-widest text-foreground text-center hover:bg-foreground hover:text-background transition-all">
                    Edit
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Customer Info -->
        <section class="glass p-6 rounded-[2rem] border border-border shadow-xl">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-5">Customer</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground block mb-0.5">Name</span>
                    <span class="text-sm font-bold text-foreground"><?php echo clean($inquiry['customer_name'] ?: 'Guest'); ?></span>
                </div>
                <div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground block mb-0.5">Email</span>
                    <a href="mailto:<?php echo clean($inquiry['email']); ?>" class="text-sm font-bold text-accent hover:underline"><?php echo clean($inquiry['email']); ?></a>
                </div>
                <?php if ($inquiry['customer_phone']): ?>
                <div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground block mb-0.5">Phone</span>
                    <a href="tel:<?php echo clean($inquiry['customer_phone']); ?>" class="text-sm font-bold text-foreground"><?php echo clean($inquiry['customer_phone']); ?></a>
                </div>
                <?php endif; ?>
                <div>
                    <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground block mb-0.5">Opened</span>
                    <span class="text-[10px] font-bold text-foreground"><?php echo date('M d, Y · H:i', strtotime($inquiry['created_at'])); ?></span>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    const container = document.getElementById('message-container');
    if (container) container.scrollTop = container.scrollHeight;
    // Focus input
    document.getElementById('admin-msg-input')?.focus();
</script>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Chat — ' . clean($inquiry['customer_name'] ?? $inquiry['name']));
?>
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if (!$id) redirect(url('admin/inquiries'));

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = clean($_POST['message']);
    $user = getUserInfo();
    
    if (sendInquiryMessage($id, $user['id'], $message)) {
        // Notify Customer
        $stmt = $db->prepare("SELECT user_id, subject FROM inquiries WHERE id = ?");
        $stmt->execute([$id]);
        $inq = $stmt->fetch();
        if ($inq && $inq['user_id']) {
            createNotification($inq['user_id'], "Expert Reply: " . ($inq['subject'] ?: 'Vehicle Inquiry'), "The administrative team has responded to your inquiry.", 'SUCCESS', 'customer/inquiries.php?id=' . $id);
        }
        setFlash('success', 'Intelligence dispatched successfully.');
    } else {
        setFlash('error', 'Message dispatch failure.');
    }
    redirect(url('admin/inquiries/chat.php?id=' . $id));
}

// Fetch Inquiry Details
$stmt = $db->prepare("
    SELECT i.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           c.make, c.model, c.year, c.slug,
           (SELECT url FROM car_images WHERE car_id = c.id LIMIT 1) as car_image
    FROM inquiries i
    LEFT JOIN users u ON i.user_id = u.id
    LEFT JOIN cars c ON i.car_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inquiry = $stmt->fetch();

if (!$inquiry) redirect(url('admin/inquiries'));

$messages = getInquiryMessages($id);
$success = getFlash('success');
$error = getFlash('error');

ob_start();
?>

<div class="mb-10 flex items-center justify-between">
    <div class="flex items-center gap-6">
        <a href="<?php echo url('admin/inquiries'); ?>" class="w-12 h-12 rounded-full bg-muted border border-border flex items-center justify-center text-foreground hover:bg-accent hover:text-white transition-all shadow-sm group">
            <i class="fas fa-arrow-left text-sm group-hover:-translate-x-1 transition-transform"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">Concierge <span class="text-gradient">Dialogue.</span></h1>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">High-fidelity communication thread with <?php echo clean($inquiry['customer_name'] ?? $inquiry['name']); ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
    <!-- Chat Area -->
    <div class="lg:col-span-3 space-y-8">
        <div class="glass rounded-[3.5rem] border border-border/50 shadow-2xl flex flex-col h-[700px] overflow-hidden">
            <!-- Messages Header -->
            <div class="p-8 border-b border-border/30 bg-muted/30 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent text-sm font-black">
                        <?php echo substr($inquiry['customer_name'] ?? $inquiry['name'], 0, 1); ?>
                    </div>
                    <div>
                        <span class="block text-xs font-black text-foreground uppercase tracking-tight"><?php echo clean($inquiry['customer_name'] ?? $inquiry['name']); ?></span>
                        <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest"><?php echo clean($inquiry['email']); ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-accent/10 rounded-full text-[8px] font-black text-accent uppercase tracking-widest"><?php echo $inquiry['status']; ?></span>
                </div>
            </div>

            <!-- Messages Window -->
            <div class="flex-1 overflow-y-auto p-8 space-y-8 bg-black/[0.01]" id="message-container">
                <?php foreach ($messages as $msg): ?>
                    <?php $isAdmin = ($msg['sender_role'] === 'admin'); ?>
                    <div class="flex <?php echo $isAdmin ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-[70%] <?php echo $isAdmin ? 'items-end' : 'items-start'; ?> flex flex-col gap-2">
                            <div class="px-6 py-4 rounded-[2rem] text-sm font-medium leading-relaxed <?php echo $isAdmin ? 'bg-foreground text-background rounded-tr-none' : 'glass border border-border/50 text-foreground rounded-tl-none'; ?>">
                                <?php echo nl2br(clean($msg['message'])); ?>
                            </div>
                            <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground opacity-60 px-2">
                                <?php echo $isAdmin ? 'Administrative Dispatch' : 'Member Inquiry'; ?> 
                                • <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Input Area -->
            <div class="p-8 border-t border-border/30 bg-muted/10">
                <form method="POST" class="relative group">
                    <textarea 
                        name="message" 
                        placeholder="Compose executive response..."
                        required
                        class="w-full bg-background border border-border rounded-3xl p-6 pr-32 text-sm font-medium focus:ring-2 focus:ring-accent outline-none min-h-[120px] transition-all shadow-inner"
                    ></textarea>
                    <div class="absolute bottom-4 right-4 flex items-center gap-3">
                        <button type="submit" class="bg-accent text-white px-8 py-3 rounded-2xl font-black uppercase tracking-widest text-[9px] shadow-lg hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                            Dispatch <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Asset Intel & Info -->
    <div class="space-y-8">
        <section class="glass p-10 rounded-[3rem] border border-border shadow-xl">
             <h3 class="text-xs font-black uppercase tracking-widest text-muted-foreground mb-8 text-center italic opacity-60">Intelligence Target</h3>
             <?php if ($inquiry['car_id']): ?>
                <div class="space-y-6">
                    <div class="aspect-video rounded-2xl overflow-hidden border border-border/50 shadow-inner">
                        <img src="<?php echo url($inquiry['car_image'] ?: 'assets/images/placeholder.jpg'); ?>" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-foreground uppercase tracking-tight mb-2"><?php echo $inquiry['year'] . ' ' . $inquiry['make'] . ' ' . $inquiry['model']; ?></h4>
                        <a href="<?php echo url('car-detail/' . $inquiry['slug']); ?>" target="_blank" class="text-[9px] font-black text-accent uppercase tracking-widest flex items-center gap-2 hover:underline">
                            Inspect Asset Record <i class="fas fa-external-link-alt text-[8px]"></i>
                        </a>
                    </div>
                </div>
             <?php else: ?>
                <p class="text-[9px] font-bold text-muted-foreground text-center italic uppercase tracking-widest">Generic Inquiry Node</p>
             <?php endif; ?>
        </section>

        <section class="glass p-10 rounded-[3rem] border border-border shadow-xl">
             <h3 class="text-xs font-black uppercase tracking-widest text-muted-foreground mb-8 italic opacity-60">Engagement Detail</h3>
             <div class="space-y-6">
                <div>
                    <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">Authenticated Customer</span>
                    <span class="text-[11px] font-bold text-foreground"><?php echo clean($inquiry['customer_name'] ?: 'Guest Signature'); ?></span>
                </div>
                <div>
                    <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">Direct Line</span>
                    <span class="text-[11px] font-bold text-foreground"><?php echo clean($inquiry['customer_phone'] ?: 'N/A'); ?></span>
                </div>
                <div>
                     <span class="block text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1 italic">Intelligence Reference</span>
                     <p class="text-[10px] font-medium text-foreground leading-relaxed"><?php echo clean($inquiry['subject']); ?></p>
                </div>
             </div>
        </section>

        <!-- Notification Status -->
        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-3xl flex items-center gap-4 text-[10px] font-black uppercase tracking-widest animate-pulse">
                <i class="fas fa-check-circle"></i> Dispatch Confirmed
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-scroll chat to bottom
    const container = document.getElementById('message-container');
    if (container) container.scrollTop = container.scrollHeight;
</script>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Concierge Dialogue');
?>
