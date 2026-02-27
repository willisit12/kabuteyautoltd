<?php
/**
 * admin/dashboard.php - Dashboard Elite
 */
require_once __DIR__ . '/../includes/layout/admin-layout.php';

$db = getDB();

// Fetch summary metrics
$totalCars = $db->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$activeCars = $db->query("SELECT COUNT(*) FROM cars WHERE status = 'AVAILABLE'")->fetchColumn();
$totalViews = $db->query("SELECT SUM(view_count) FROM cars")->fetchColumn() ?: 0;
$totalInquiries = $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();

// Get recent car listings (limit 10 for dashboard)
$stmt = $db->prepare("SELECT * FROM cars ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$cars = $stmt->fetchAll();

// Get recent inquiries
$stmt = $db->prepare("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentInquiries = $stmt->fetchAll();

// Get all users (if admin)
$users = [];
if ($user && $user['role'] === 'admin') {
    $stmt = $db->prepare("SELECT id, name, email, role, avatar_url, last_login FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll();
}

$success = getFlash('success');
$error = getFlash('error');

// Capture Content for Layout
ob_start();
?>

<?php if ($success): ?>
    <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold">
        <i class="fas fa-check-circle text-xl"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-[2rem] mb-8 flex items-center gap-4 text-sm font-bold animate-pulse">
        <i class="fas fa-exclamation-triangle text-xl"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Metrics Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
    <!-- Total Inventory -->
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 group hover:border-accent transition-all duration-500 overflow-hidden relative">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-accent/5 rounded-full blur-2xl group-hover:bg-accent/10 transition-all"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-14 h-14 bg-accent/10 rounded-2xl flex items-center justify-center text-accent">
                <i class="fas fa-car-side text-2xl"></i>
            </div>
            <span class="text-[10px] font-black tracking-widest text-accent uppercase bg-accent/5 px-3 py-1 rounded-full">Inventory</span>
        </div>
        <h3 class="text-4xl font-black text-foreground tracking-tighter mb-1"><?php echo $totalCars; ?></h3>
        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Total Vehicles</p>
    </div>

    <!-- Active Listings -->
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 group hover:border-accent transition-all duration-500 overflow-hidden relative">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/5 rounded-full blur-2xl group-hover:bg-green-500/10 transition-all"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-500">
                <i class="fas fa-check-double text-2xl"></i>
            </div>
            <span class="text-[10px] font-black tracking-widest text-green-500 uppercase bg-green-500/5 px-3 py-1 rounded-full">Live</span>
        </div>
        <h3 class="text-4xl font-black text-foreground tracking-tighter mb-1"><?php echo $activeCars; ?></h3>
        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Active Listings</p>
    </div>

    <!-- Total Engagement -->
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 group hover:border-accent transition-all duration-500 overflow-hidden relative">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-all"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-500">
                <i class="fas fa-eye text-2xl"></i>
            </div>
            <span class="text-[10px] font-black tracking-widest text-blue-500 uppercase bg-blue-500/5 px-3 py-1 rounded-full">Traffic</span>
        </div>
        <h3 class="text-4xl font-black text-foreground tracking-tighter mb-1"><?php echo number_format($totalViews); ?></h3>
        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Collection Views</p>
    </div>

    <!-- Active Inquiries -->
    <div class="glass p-8 rounded-[2.5rem] border border-border/50 group hover:border-accent transition-all duration-500 overflow-hidden relative">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/5 rounded-full blur-2xl group-hover:bg-purple-500/10 transition-all"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500">
                <i class="fas fa-envelope-open-text text-2xl"></i>
            </div>
            <span class="text-[10px] font-black tracking-widest text-purple-500 uppercase bg-purple-500/5 px-3 py-1 rounded-full">Leads</span>
        </div>
        <h3 class="text-4xl font-black text-foreground tracking-tighter mb-1"><?php echo $totalInquiries; ?></h3>
        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Total Inquiries</p>
    </div>

    <!-- Website Shortcut (New) -->
    <a href="<?php echo url(); ?>" target="_blank" class="glass p-8 rounded-[2.5rem] border border-accent/20 bg-accent/[0.02] group hover:bg-accent/[0.05] hover:border-accent transition-all duration-500 overflow-hidden relative flex flex-col justify-center items-center text-center">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-accent/5 rounded-full blur-2xl group-hover:bg-accent/10 transition-all"></div>
        <div class="w-16 h-16 bg-accent rounded-2xl flex items-center justify-center text-white mb-4 shadow-lg group-hover:scale-110 transition-transform">
            <i class="fas fa-external-link-alt text-2xl"></i>
        </div>
        <h3 class="text-xl font-black text-foreground tracking-tighter mb-1 uppercase">Live Showroom</h3>
        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em]">View Public Website</p>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Recent Management (Cars) -->
    <div class="lg:col-span-2 space-y-8">
        <div class="flex justify-between items-end mb-4 px-2">
            <div>
                <h3 class="text-2xl font-black text-foreground tracking-tighter uppercase transition-colors">Vehicle Fleet</h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mt-1">Latest 5 Listings</p>
            </div>
            <a href="cars/" class="text-xs font-black uppercase tracking-widest text-accent hover:underline flex items-center gap-2">
                <span>View All</span>
                <i class="fas fa-chevron-right text-[8px]"></i>
            </a>
        </div>

        <div class="glass rounded-3xl border border-border/50 overflow-hidden shadow-sm bg-card/30">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-muted/30 border-b border-border/50">
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Vehicle Asset</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Valuation</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-center">Analytics</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-right">Operations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/20">
                        <?php foreach ($cars as $car): ?>
                        <tr class="hover:bg-accent/[0.02] transition-colors group">
                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="font-black text-foreground tracking-tight text-base"><?php echo clean($car['year'] . ' ' . $car['make']); ?></span>
                                    <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5"><?php echo clean($car['model']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-bold text-foreground tabular-nums text-sm"><?php echo formatPrice($car['price']); ?></div>
                                <div class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">MSRP Reference</div>
                            </td>
                            <td class="px-6 py-5">
                                <?php if ($car['status'] === 'AVAILABLE'): ?>
                                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-500/10 border border-green-500/20 text-green-500 rounded-full">
                                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                                        <span class="text-[9px] font-black uppercase tracking-widest">Active</span>
                                    </div>
                                <?php else: ?>
                                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/10 border border-red-500/20 text-red-500 rounded-full">
                                        <div class="w-1.5 h-1.5 bg-red-500 rounded-full"></div>
                                        <span class="text-[9px] font-black uppercase tracking-widest"><?php echo $car['status']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-muted/50 rounded-lg">
                                    <i class="fas fa-chart-line text-[10px] text-accent"></i>
                                    <span class="text-xs font-black text-foreground tabular-nums"><?php echo number_format($car['view_count']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="cars/edit.php?id=<?php echo $car['id']; ?>" 
                                       class="w-9 h-9 rounded-xl border border-border bg-background hover:bg-accent hover:border-accent hover:text-white transition-all flex items-center justify-center text-muted-foreground group/btn"
                                       title="Edit Asset">
                                         <i class="fas fa-pen-to-square text-xs"></i>
                                    </a>
                                    <a href="cars/delete.php?id=<?php echo $car['id']; ?>" 
                                       onclick="return confirm('Archive this masterpiece?');" 
                                       class="w-9 h-9 rounded-xl border border-border bg-background hover:bg-red-500 hover:border-red-500 hover:text-white transition-all flex items-center justify-center text-muted-foreground"
                                       title="Archive Asset">
                                        <i class="fas fa-trash-can text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($cars)): ?>
            <div class="p-20 text-center bg-muted/10">
                <div class="w-20 h-20 bg-muted/20 rounded-3xl flex items-center justify-center mx-auto mb-6 text-muted-foreground/40">
                    <i class="fas fa-box-open text-3xl"></i>
                </div>
                <h4 class="text-sm font-black uppercase tracking-[0.2em] text-foreground mb-1">Vault Offline</h4>
                <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">The collection is currently empty.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar Activity (Users/Inquiries) -->
    <div class="space-y-12">
        <!-- User Access -->
        <?php if ($user && $user['role'] === 'admin'): ?>
        <div>
            <div class="flex justify-between items-end mb-6 px-2">
                <div>
                    <h3 class="text-xl font-black text-foreground tracking-tighter uppercase leading-none">Access Control</h3>
                </div>
                <a href="users/" class="text-[9px] font-black uppercase tracking-widest text-accent hover:underline flex items-center gap-1">View All <i class="fas fa-chevron-right text-[7px]"></i></a>
            </div>
            
            <div class="space-y-4">
                <?php foreach ($users as $user): ?>
                <div class="glass p-5 rounded-3xl border border-border/50 flex items-center gap-4 group hover:border-accent transition-all duration-500">
                    <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center text-accent group-hover:bg-accent group-hover:text-white transition-all overflow-hidden font-black">
                        <?php if ($user['avatar_url']): ?>
                            <img src="<?php echo $user['avatar_url']; ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo substr($user['name'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-black text-foreground tracking-tight truncate"><?php echo clean($user['name']); ?></p>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest truncate"><?php echo clean($user['email']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Correspodence -->
        <div>
            <div class="flex justify-between items-end mb-6 px-2">
                <div>
                    <h3 class="text-xl font-black text-foreground tracking-tighter uppercase leading-none">Recent Leads</h3>
                </div>
                <a href="inquiries/" class="text-[9px] font-black uppercase tracking-widest text-accent hover:underline flex items-center gap-1">View All <i class="fas fa-chevron-right text-[7px]"></i></a>
            </div>
            
            <div class="space-y-4">
                <?php foreach ($recentInquiries as $lead): ?>
                <div class="glass p-5 rounded-3xl border border-border/50 group hover:border-accent transition-all duration-500">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-black text-foreground tracking-tight text-sm"><?php echo clean($lead['name']); ?></p>
                        <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground opacity-50"><?php echo date('H:i', strtotime($lead['created_at'])); ?></span>
                    </div>
                    <p class="text-[10px] font-bold text-muted-foreground leading-relaxed line-clamp-2 italic italic opacity-70">
                        "<?php echo clean($lead['message']); ?>"
                    </p>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($recentInquiries)): ?>
                <p class="text-[10px] font-bold text-muted-foreground/40 text-center uppercase tracking-widest py-8">No Recent Correlation</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Executive Dashboard');
?>
