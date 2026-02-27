<?php
/**
 * admin/users/index.php - User Management List
 * With pagination, search, sorting, and per-page controls
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

// Only admins can manage users
if (!isAdminRole()) {
    setFlash('error', 'You do not have permission to manage users');
    redirect('../dashboard.php');
}

$db = getDB();

// --- Query Parameters ---
$search = trim($_GET['q'] ?? '');
$role_filter = $_GET['role'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');
$per_page = intval($_GET['per_page'] ?? 10);
$page = max(1, intval($_GET['page'] ?? 1));

// Whitelist sort columns
$allowed_sorts = ['name', 'email', 'role', 'last_login', 'created_at'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';
if (!in_array($per_page, [5, 10, 25, 50, 100])) $per_page = 10;

// --- Build Query ---
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $sp = "%{$search}%";
    $params = array_merge($params, [$sp, $sp]);
}

if (!empty($role_filter) && in_array($role_filter, ['admin', 'user'])) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM users {$whereSQL}");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $per_page));
$page = min($page, $totalPages);
$offset = ($page - 1) * $per_page;

// Fetch paginated results
$dataStmt = $db->prepare("SELECT id, name, email, role, last_login, created_at FROM users {$whereSQL} ORDER BY `{$sort}` {$order} LIMIT {$per_page} OFFSET {$offset}");
$dataStmt->execute($params);
$users_list = $dataStmt->fetchAll();

$success = getFlash('success');
$error = getFlash('error');

// Helper to build URLs preserving params
function buildUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) $params[$k] = $v;
    return 'index.php?' . http_build_query($params);
}

function sortLink($col, $label, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort === $col)
        ? ($currentOrder === 'ASC' ? '<i class="fas fa-sort-up ml-1 text-accent"></i>' : '<i class="fas fa-sort-down ml-1 text-accent"></i>')
        : '<i class="fas fa-sort ml-1 opacity-30"></i>';
    $url = buildUrl(['sort' => $col, 'order' => $newOrder, 'page' => 1]);
    return '<a href="' . $url . '" class="hover:text-accent transition-colors inline-flex items-center">' . $label . $icon . '</a>';
}

ob_start();
?>

<!-- Action Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4 px-2">
    <div>
        <h3 class="text-2xl font-black text-foreground tracking-tighter uppercase transition-colors">Access Control</h3>
        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mt-1">
            <?php echo number_format($totalItems); ?> Registered Identities
        </p>
    </div>
    <a href="add.php" class="btn-premium bg-accent text-white px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:scale-105 active:scale-95 transition-all shadow-[0_10px_20px_rgba(249,115,22,0.3)] flex items-center gap-2">
        <i class="fas fa-plus"></i>
        <span>Forge Identity</span>
    </a>
</div>

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

<!-- Search & Filter Bar -->
<div class="glass rounded-[2rem] border border-border/50 p-6 mb-6 bg-card/30">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 min-w-0">
            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 ml-1">Search Identities</label>
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                <input type="text" name="q" value="<?php echo clean($search); ?>" placeholder="Name, email..."
                       class="w-full bg-background/50 border border-border text-foreground pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold text-sm outline-none">
            </div>
        </div>

        <div class="w-full md:w-48">
            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 ml-1">Role</label>
            <select name="role" class="w-full bg-background/50 border border-border text-foreground px-4 py-3 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold text-sm outline-none appearance-none">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin Elite</option>
                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Standard User</option>
            </select>
        </div>

        <div class="w-full md:w-36">
            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 ml-1">Per Page</label>
            <select name="per_page" class="w-full bg-background/50 border border-border text-foreground px-4 py-3 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold text-sm outline-none appearance-none">
                <?php foreach ([5, 10, 25, 50, 100] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $per_page === $opt ? 'selected' : ''; ?>><?php echo $opt; ?> items</option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
        <input type="hidden" name="order" value="<?php echo $order; ?>">

        <div class="flex gap-2">
            <button type="submit" class="bg-accent text-white px-5 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <i class="fas fa-filter"></i> Apply
            </button>
            <a href="index.php" class="bg-muted border border-border text-muted-foreground px-5 py-3 rounded-xl font-black uppercase tracking-widest text-[10px] hover:text-foreground transition-all flex items-center gap-2">
                <i class="fas fa-times"></i> Clear
            </a>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="glass rounded-[2rem] border border-border/50 overflow-hidden shadow-xl bg-card/50">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-muted/50 border-b border-border/50">
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('name', 'Identity', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('email', 'Communication', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('role', 'Rank / Role', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-center">
                        <?php echo sortLink('last_login', 'Last Active', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('created_at', 'Joined', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-right">Operations</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/20">
                <?php foreach ($users_list as $u): ?>
                <tr class="hover:bg-accent/[0.03] transition-colors group">
                    <td class="px-8 py-6">
                        <div class="flex flex-col">
                            <span class="font-black text-foreground tracking-tight text-lg"><?php echo clean($u['name']); ?></span>
                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mt-1">ID: #<?php echo $u['id']; ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="text-xs font-bold text-muted-foreground"><?php echo clean($u['email']); ?></span>
                    </td>
                    <td class="px-8 py-6">
                        <?php if ($u['role'] === 'admin'): ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-accent/10 border border-accent/20 text-accent rounded-full">
                                <i class="fas fa-shield-halved text-[10px]"></i>
                                <span class="text-[9px] font-black uppercase tracking-widest">Admin Elite</span>
                            </div>
                        <?php else: ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-muted border border-border text-foreground rounded-full">
                                <i class="fas fa-user text-[10px]"></i>
                                <span class="text-[9px] font-black uppercase tracking-widest">Standard User</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-8 py-6 text-center">
                        <?php if ($u['last_login']): ?>
                            <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                                <?php echo date('M d, Y', strtotime($u['last_login'])); ?>
                            </span>
                            <div class="text-[9px] font-bold text-muted-foreground/50 mt-1">
                                <?php echo date('H:i', strtotime($u['last_login'])); ?>
                            </div>
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-muted-foreground/40 italic">Never active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-8 py-6">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </span>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="edit.php?id=<?php echo $u['id']; ?>" 
                               class="w-10 h-10 rounded-xl border border-border bg-background hover:bg-accent hover:border-accent hover:text-white transition-all flex items-center justify-center text-muted-foreground"
                               title="Edit Permissions">
                                <i class="fas fa-pen-clip text-xs"></i>
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="delete.php?id=<?php echo $u['id']; ?>" 
                               onclick="return confirm('Eradicate this identity from the framework permanently?');" 
                               class="w-10 h-10 rounded-xl border border-border bg-background hover:bg-red-500 hover:border-red-500 hover:text-white transition-all flex items-center justify-center text-muted-foreground"
                               title="Revoke & Purge">
                                <i class="fas fa-user-minus text-xs"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (empty($users_list)): ?>
    <div class="p-24 text-center bg-muted/10">
        <div class="w-24 h-24 bg-muted/20 rounded-[2rem] flex items-center justify-center mx-auto mb-8 text-muted-foreground/40">
            <i class="fas fa-users-slash text-4xl"></i>
        </div>
        <h4 class="text-lg font-black uppercase tracking-tighter text-foreground mb-2">
            <?php echo !empty($search) || !empty($role_filter) ? 'No Matches Found' : 'No Users'; ?>
        </h4>
        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest max-w-sm mx-auto leading-relaxed">
            <?php echo !empty($search) || !empty($role_filter)
                ? 'No identities match your current filters.'
                : 'No users have been registered yet.'; ?>
        </p>
        <?php if (!empty($search) || !empty($role_filter)): ?>
            <a href="index.php" class="inline-block mt-8 text-accent font-black text-[10px] uppercase tracking-widest border-b-2 border-accent pb-1 hover:text-foreground hover:border-foreground transition-all">Clear Filters</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex flex-col sm:flex-row justify-between items-center mt-8 gap-4 px-2">
    <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">
        Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $totalItems); ?> of <?php echo number_format($totalItems); ?> identities
    </div>
    <div class="flex items-center gap-2">
        <a href="<?php echo buildUrl(['page' => 1]); ?>" class="w-10 h-10 rounded-xl border <?php echo $page <= 1 ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center" title="First"><i class="fas fa-angles-left text-xs"></i></a>
        <a href="<?php echo buildUrl(['page' => max(1, $page - 1)]); ?>" class="w-10 h-10 rounded-xl border <?php echo $page <= 1 ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center" title="Previous"><i class="fas fa-chevron-left text-xs"></i></a>
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        if ($endPage - $startPage < 4) { $startPage = max(1, $endPage - 4); $endPage = min($totalPages, $startPage + 4); }
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a href="<?php echo buildUrl(['page' => $i]); ?>" class="w-10 h-10 rounded-xl border <?php echo $i === $page ? 'border-accent bg-accent text-white shadow-[0_5px_15px_rgba(249,115,22,0.3)]' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center font-black text-xs"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo buildUrl(['page' => min($totalPages, $page + 1)]); ?>" class="w-10 h-10 rounded-xl border <?php echo $page >= $totalPages ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center" title="Next"><i class="fas fa-chevron-right text-xs"></i></a>
        <a href="<?php echo buildUrl(['page' => $totalPages]); ?>" class="w-10 h-10 rounded-xl border <?php echo $page >= $totalPages ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center" title="Last"><i class="fas fa-angles-right text-xs"></i></a>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Identity Directory');
?>
