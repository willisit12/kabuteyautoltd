<?php
/**
 * admin/inquiries/index.php
 * Administrative Correspondence Dashboard
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

requireAdmin();

$db = getDB();
$status_filter = $_GET['status'] ?? 'ALL';

$query = "
    SELECT i.*, u.name as customer_name, u.avatar_url, 
           c.make, c.model, c.year, c.slug
    FROM inquiries i
    LEFT JOIN users u ON i.user_id = u.id
    LEFT JOIN cars c ON i.car_id = c.id
";

if ($status_filter !== 'ALL') {
    $query .= " WHERE i.status = " . $db->quote($status_filter);
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $db->query($query);
$inquiries = $stmt->fetchAll();

ob_start();
?>

<div class="mb-12">
    <h1 class="text-4xl font-black text-foreground tracking-tighter uppercase leading-none mb-2">Correspondence <span class="text-gradient">Inbox.</span></h1>
    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground opacity-60">Manage high-fidelity customer inquiries and expert consultations</p>
</div>

<!-- Filters -->
<div class="flex flex-wrap gap-3 mb-10">
    <?php foreach(['ALL', 'PENDING', 'REPLIED', 'ARCHIVED'] as $s): ?>
        <a href="?status=<?php echo $s; ?>" 
           class="px-5 py-2.5 rounded-full border text-[9px] font-black uppercase tracking-widest transition-all <?php echo $status_filter === $s ? 'bg-foreground text-background border-foreground shadow-lg' : 'bg-muted/30 border-border/50 text-muted-foreground hover:border-accent hover:text-accent'; ?>">
            <?php echo $s; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 gap-6">
    <?php if (empty($inquiries)): ?>
        <div class="glass p-20 rounded-[4rem] border border-dashed border-border/50 text-center">
            <div class="opacity-20 mb-4"><i class="fas fa-inbox text-5xl"></i></div>
            <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest leading-loose">No correspondence records detected.</p>
        </div>
    <?php else: ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <div class="glass rounded-[3rem] border border-border/50 p-8 hover:border-accent transition-all duration-500 relative overflow-hidden group">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 rounded-2xl bg-accent/10 flex items-center justify-center text-accent font-black text-xl">
                                        <?php echo substr($inq['customer_name'] ?? $inq['name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-black text-foreground uppercase tracking-tighter"><?php echo clean($inq['customer_name'] ?? $inq['name']); ?></h3>
                                            <?php if ($inq['status'] === 'PENDING'): ?>
                                                <span class="w-2 h-2 bg-accent rounded-full animate-pulse"></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest flex items-center gap-4">
                                            <span><i class="fas fa-envelope mr-1.5 opacity-60"></i> <?php echo clean($inq['email']); ?></span>
                                            <?php if ($inq['phone']): ?>
                                                <span><i class="fas fa-phone mr-1.5 opacity-60"></i> <?php echo clean($inq['phone']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex-1 md:px-12">
                                    <span class="block text-[8px] font-black text-accent uppercase tracking-widest mb-2 italic">Intelligence Subject</span>
                                    <h4 class="text-xs font-black text-foreground uppercase tracking-tight leading-relaxed line-clamp-1">
                                        <?php echo clean($inq['subject'] ?: 'Vehicle Inquiry'); ?>
                                        <?php if ($inq['make']): ?>
                                            <span class="text-muted-foreground ml-2 opacity-60">[<?php echo $inq['year'] . ' ' . $inq['make']; ?>]</span>
                                        <?php endif; ?>
                                    </h4>
                                </div>

                                <div class="flex items-center gap-8">
                                    <div class="text-right hidden sm:block">
                                        <span class="block text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1">Dispatch Time</span>
                                        <span class="text-[10px] font-bold text-foreground tabular-nums"><?php echo date('M d, H:i', strtotime($inq['created_at'])); ?></span>
                                    </div>
                                    <a href="<?php echo url('admin/inquiries/chat.php?id=' . $inq['id']); ?>" class="btn-premium bg-foreground text-background hover:bg-accent hover:text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[9px] shadow-lg transition-all flex items-center gap-3">
                                        Initiate Chat <i class="fas fa-comments"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Correspondence Inbox');
?>
