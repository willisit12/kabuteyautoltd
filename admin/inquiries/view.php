<?php
/**
 * admin/inquiries/view.php - View & Modify Inquiry
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security integrity compromised.');
    } else {
        $status = clean($_POST['status'] ?? 'PENDING');
        try {
            $updateQry = "UPDATE inquiries SET status = ?";
            $params = [$status];

            if ($status === 'REPLIED') {
                $updateQry .= ", replied_at = NOW()";
            }
            $updateQry .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $db->prepare($updateQry);
            $stmt->execute($params);
            setFlash('success', 'Correspondence status updated.');
        } catch (PDOException $e) {
            setFlash('error', 'Update failure: ' . $e->getMessage());
        }
    }
    redirect('view.php?id=' . $id);
}

// Fetch details
$stmt = $db->prepare("
    SELECT i.*, c.make, c.model, c.year, c.price 
    FROM inquiries i 
    LEFT JOIN cars c ON i.car_id = c.id 
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    setFlash('error', 'Correspondence signature not found.');
    redirect('index.php');
}

$success = getFlash('success');
$error = getFlash('error');

ob_start();
?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="mb-12 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="index.php" class="w-12 h-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-white transition-all shadow-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-black text-foreground tracking-tighter uppercase mb-2">Message Intel</h1>
                <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Correspondence Reference #<?php echo $id; ?></p>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold relative z-10">
            <i class="fas fa-check-circle text-xl"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold animate-pulse relative z-10">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Main Message Content -->
        <div class="md:col-span-2 space-y-8">
            <div class="glass p-10 rounded-[3rem] border border-border/50 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-10 opacity-5">
                    <i class="fas fa-quote-right text-8xl text-foreground"></i>
                </div>
                
                <h3 class="text-xs font-black text-muted-foreground tracking-widest uppercase mb-8 flex items-center gap-3">
                    <span class="w-2 h-2 bg-accent rounded-full"></span>
                    Transmitted Payload
                </h3>

                <div class="space-y-6 relative z-10">
                    <div>
                        <span class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2">Sender Protocol</span>
                        <p class="text-xl font-bold text-foreground">
                            <?php echo clean($inquiry['name']); ?>
                        </p>
                    </div>

                    <div class="p-6 bg-muted/30 rounded-3xl border border-border/50">
                        <span class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-4">Message Body</span>
                        <p class="text-base text-foreground/80 leading-relaxed font-medium whitespace-pre-wrap"><?php echo clean($inquiry['message']); ?></p>
                    </div>

                    <div class="flex items-center gap-6 pt-6 border-t border-border/50">
                        <a href="mailto:<?php echo clean($inquiry['email']); ?>" class="flex items-center gap-3 text-sm font-bold text-foreground hover:text-accent transition-colors">
                            <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <?php echo clean($inquiry['email']); ?>
                        </a>
                        
                        <?php if ($inquiry['phone']): ?>
                        <a href="tel:<?php echo clean($inquiry['phone']); ?>" class="flex items-center gap-3 text-sm font-bold text-foreground hover:text-accent transition-colors">
                            <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">
                                <i class="fas fa-phone"></i>
                            </div>
                            <?php echo clean($inquiry['phone']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-8">
            <!-- Subject Asset -->
            <?php if ($inquiry['car_id']): ?>
            <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl">
                <h3 class="text-[10px] font-black text-muted-foreground tracking-widest uppercase mb-6 flex items-center gap-2">
                    <i class="fas fa-car-side text-accent"></i>
                    Subject Asset
                </h3>
                
                <a href="../cars/edit.php?id=<?php echo $inquiry['car_id']; ?>" class="block group">
                    <div class="bg-muted/50 p-5 rounded-2xl border border-border group-hover:border-accent group-hover:shadow-lg transition-all">
                        <p class="text-[10px] font-black text-accent uppercase tracking-widest mb-1"><?php echo $inquiry['year']; ?> <?php echo clean($inquiry['make']); ?></p>
                        <p class="text-lg font-black text-foreground tracking-tight leading-none mb-3"><?php echo clean($inquiry['model']); ?></p>
                        <p class="text-sm font-bold text-foreground tabular-nums"><?php echo formatPrice($inquiry['price']); ?></p>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Status Control -->
            <div class="glass p-8 rounded-[2.5rem] border border-border/50 shadow-xl relative overflow-hidden">
                <h3 class="text-[10px] font-black text-muted-foreground tracking-widest uppercase mb-6 flex items-center gap-2 relative z-10">
                    <i class="fas fa-sliders-h text-accent"></i>
                    Status Protocol
                </h3>

                <form method="POST" class="relative z-10 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 p-4 rounded-xl border <?php echo $inquiry['status'] === 'PENDING' ? 'border-amber-500 bg-amber-500/10' : 'border-border bg-background/50'; ?> cursor-pointer transition-colors group">
                            <input type="radio" name="status" value="PENDING" class="text-amber-500 focus:ring-amber-500 focus:ring-offset-background bg-background border-border" <?php echo $inquiry['status'] === 'PENDING' ? 'checked' : ''; ?>>
                            <span class="text-[10px] font-black uppercase tracking-widest <?php echo $inquiry['status'] === 'PENDING' ? 'text-amber-500' : 'text-foreground group-hover:text-amber-500'; ?> transition-colors">Pending Review</span>
                        </label>
                        
                        <label class="flex items-center gap-3 p-4 rounded-xl border <?php echo $inquiry['status'] === 'REPLIED' ? 'border-green-500 bg-green-500/10' : 'border-border bg-background/50'; ?> cursor-pointer transition-colors group">
                            <input type="radio" name="status" value="REPLIED" class="text-green-500 focus:ring-green-500 focus:ring-offset-background bg-background border-border" <?php echo $inquiry['status'] === 'REPLIED' ? 'checked' : ''; ?>>
                            <span class="text-[10px] font-black uppercase tracking-widest <?php echo $inquiry['status'] === 'REPLIED' ? 'text-green-500' : 'text-foreground group-hover:text-green-500'; ?> transition-colors">Responded</span>
                        </label>
                        
                        <label class="flex items-center gap-3 p-4 rounded-xl border <?php echo $inquiry['status'] === 'ARCHIVED' ? 'border-foreground/30 bg-muted/50' : 'border-border bg-background/50'; ?> cursor-pointer transition-colors group">
                            <input type="radio" name="status" value="ARCHIVED" class="text-foreground focus:ring-foreground focus:ring-offset-background bg-background border-border" <?php echo $inquiry['status'] === 'ARCHIVED' ? 'checked' : ''; ?>>
                            <span class="text-[10px] font-black uppercase tracking-widest text-foreground group-hover:text-foreground/70 transition-colors">Archived</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-accent text-white py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] hover:scale-[1.03] active:scale-[0.98] transition-all shadow-[0_10px_20px_rgba(249,115,22,0.3)]">
                        Commit Status
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-border/50">
                    <p class="text-[8px] font-black uppercase tracking-widest text-muted-foreground flex justify-between">
                        <span>Received</span>
                        <span class="text-foreground"><?php echo date('M d, Y - H:i', strtotime($inquiry['created_at'])); ?></span>
                    </p>
                    <?php if ($inquiry['replied_at']): ?>
                    <p class="text-[8px] font-black uppercase tracking-widest text-muted-foreground flex justify-between mt-2">
                        <span>Responded</span>
                        <span class="text-foreground"><?php echo date('M d, Y - H:i', strtotime($inquiry['replied_at'])); ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Correspondence Details');
?>
