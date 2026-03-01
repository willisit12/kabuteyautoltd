<?php
/**
 * admin/cars/index.php - Cars Management List
 * With pagination, search, sorting, and per-page controls
 */
require_once __DIR__ . '/../../includes/layout/admin-layout.php';

$db = getDB();

// --- Query Parameters ---
$search = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC');
$per_page = intval($_GET['per_page'] ?? 10);
$page = max(1, intval($_GET['page'] ?? 1));

// Whitelist sort columns
$allowed_sorts = ['make', 'price', 'year', 'status', 'view_count', 'created_at'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';
if (!in_array($per_page, [5, 10, 25, 50, 100])) $per_page = 10;

// --- Build Query ---
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(make LIKE ? OR model LIKE ? OR CAST(year AS CHAR) LIKE ? OR vin LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($status_filter) && in_array($status_filter, ['AVAILABLE', 'RESERVED', 'SOLD', 'ARCHIVED'])) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM cars {$whereSQL}");
$countStmt->execute($params);
$totalCars = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalCars / $per_page));
$page = min($page, $totalPages);
$offset = ($page - 1) * $per_page;

// Fetch paginated results
$dataStmt = $db->prepare("
    SELECT c.*, 
    (SELECT url FROM car_images WHERE car_id = c.id ORDER BY `order` ASC LIMIT 1) as primary_image
    FROM cars c 
    {$whereSQL} 
    ORDER BY `{$sort}` {$order} 
    LIMIT {$per_page} OFFSET {$offset}
");
$dataStmt->execute($params);
$cars = $dataStmt->fetchAll();

$success = getFlash('success');
$error = getFlash('error');

// Helper to build URLs preserving params
function buildUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    return 'index.php?' . http_build_query($params);
}

// Helper for sort link
function sortLink($col, $label, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentSort === $col) {
        $icon = $currentOrder === 'ASC' ? '<i class="fas fa-sort-up ml-1 text-accent"></i>' : '<i class="fas fa-sort-down ml-1 text-accent"></i>';
    } else {
        $icon = '<i class="fas fa-sort ml-1 opacity-30"></i>';
    }
    $url = buildUrl(['sort' => $col, 'order' => $newOrder, 'page' => 1]);
    return '<a href="' . $url . '" class="hover:text-accent transition-colors inline-flex items-center">' . $label . $icon . '</a>';
}

ob_start();
?>

<!-- Action Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4 px-2">
    <div>
        <h3 class="text-2xl font-black text-foreground tracking-tighter uppercase transition-colors">Vehicle Fleet</h3>
        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mt-1">
            <?php echo number_format($totalCars); ?> Total Assets
        </p>
    </div>
    <div class="flex items-center gap-3">
        <a href="import.php" class="bg-muted border border-border text-foreground px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:scale-105 active:scale-95 transition-all flex items-center gap-2 hover:border-accent hover:text-accent">
            <i class="fas fa-file-import"></i>
            <span>Bulk Import</span>
        </a>
        <a href="add.php" class="btn-premium bg-accent text-white px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:scale-105 active:scale-95 transition-all shadow-[0_10px_20px_rgba(249,115,22,0.3)] flex items-center gap-2">
            <i class="fas fa-plus"></i>
            <span>Forge Asset</span>
        </a>
    </div>
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
        <!-- Search -->
        <div class="flex-1 min-w-0">
            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 ml-1">Search Inventory</label>
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground/30 group-focus-within:text-accent transition-colors"></i>
                <input type="text" name="q" value="<?php echo clean($search); ?>" placeholder="Make, model, year, VIN..."
                       class="w-full bg-background/50 border border-border text-foreground pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold text-sm outline-none">
            </div>
        </div>

        <!-- Status Filter -->
        <div class="w-full md:w-48">
            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 ml-1">Status</label>
            <select name="status" class="w-full bg-background/50 border border-border text-foreground px-4 py-3 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold text-sm outline-none appearance-none">
                <option value="">All Statuses</option>
                <option value="AVAILABLE" <?php echo $status_filter === 'AVAILABLE' ? 'selected' : ''; ?>>Available</option>
                <option value="RESERVED" <?php echo $status_filter === 'RESERVED' ? 'selected' : ''; ?>>Reserved</option>
                <option value="SOLD" <?php echo $status_filter === 'SOLD' ? 'selected' : ''; ?>>Sold</option>
                <option value="ARCHIVED" <?php echo $status_filter === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>

        <!-- Per Page -->
        <div class="w-full md:w-36">
            <label class="block text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 ml-1">Per Page</label>
            <select name="per_page" class="w-full bg-background/50 border border-border text-foreground px-4 py-3 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition font-bold text-sm outline-none appearance-none">
                <?php foreach ([5, 10, 25, 50, 100] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $per_page === $opt ? 'selected' : ''; ?>><?php echo $opt; ?> items</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Hidden sort params -->
        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
        <input type="hidden" name="order" value="<?php echo $order; ?>">

        <!-- Action Buttons -->
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

<!-- Inventory Table -->
<div class="glass rounded-[2rem] border border-border/50 overflow-hidden shadow-xl bg-card/50">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-muted/50 border-b border-border/50">
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground w-20">Visual</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('make', 'Vehicle Asset', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('price', 'Valuation', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                        <?php echo sortLink('status', 'Status', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-center">
                        <?php echo sortLink('view_count', 'Traffic', $sort, $order); ?>
                    </th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-right">Operations</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/20">
                <?php foreach ($cars as $car): ?>
                <tr class="hover:bg-accent/[0.03] transition-colors group">
                    <td class="px-8 py-6">
                        <div class="w-16 h-12 rounded-xl border border-border/50 bg-muted/30 overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                            <?php if ($car['primary_image']): ?>
                                <img src="<?php echo url($car['primary_image']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-muted/20 text-muted-foreground/30">
                                    <i class="fas fa-camera text-xs"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex flex-col">
                            <span class="font-black text-foreground tracking-tight text-lg"><?php echo clean($car['year'] . ' ' . $car['make']); ?></span>
                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mt-1"><?php echo clean($car['model']); ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="font-black text-foreground tabular-nums text-base"><?php echo formatPrice($car['price'], $car['price_unit'] ?? null); ?></div>
                        <div class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest mt-1"><?php echo formatMileage($car['mileage']); ?></div>
                    </td>
                    <td class="px-8 py-6">
                        <?php
                        $statusColors = [
                            'AVAILABLE' => ['bg-green-500/10', 'border-green-500/20', 'text-green-500', 'bg-green-500'],
                            'RESERVED'  => ['bg-amber-500/10', 'border-amber-500/20', 'text-amber-500', 'bg-amber-500'],
                            'SOLD'      => ['bg-blue-500/10', 'border-blue-500/20', 'text-blue-500', 'bg-blue-500'],
                            'ARCHIVED'  => ['bg-red-500/10', 'border-red-500/20', 'text-red-500', 'bg-red-500'],
                        ];
                        $sc = $statusColors[$car['status']] ?? $statusColors['ARCHIVED'];
                        ?>
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 <?php echo $sc[0]; ?> border <?php echo $sc[1]; ?> <?php echo $sc[2]; ?> rounded-full">
                            <div class="w-1.5 h-1.5 <?php echo $sc[3]; ?> rounded-full <?php echo $car['status'] === 'AVAILABLE' ? 'animate-pulse' : ''; ?>"></div>
                            <span class="text-[9px] font-black uppercase tracking-widest"><?php echo $car['status']; ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-6 text-center">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-muted/50 rounded-xl">
                            <i class="fas fa-chart-line text-[10px] text-accent"></i>
                            <span class="text-sm font-black text-foreground tabular-nums"><?php echo number_format($car['view_count']); ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="<?php echo url('car-detail/' . $car['slug']); ?>" target="_blank"
                               class="w-10 h-10 rounded-xl border border-border bg-background hover:bg-foreground hover:text-background hover:border-foreground transition-all flex items-center justify-center text-muted-foreground"
                               title="View Live">
                                <i class="fas fa-external-link-alt text-xs"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $car['id']; ?>" 
                               class="w-10 h-10 rounded-xl border border-border bg-background hover:bg-accent hover:border-accent hover:text-white transition-all flex items-center justify-center text-muted-foreground"
                               title="Edit Content">
                                <i class="fas fa-pen text-xs"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $car['id']; ?>" 
                               onclick="return confirm('Obliterate this masterpiece from existence?');" 
                               class="w-10 h-10 rounded-xl border border-border bg-background hover:bg-red-500 hover:border-red-500 hover:text-white transition-all flex items-center justify-center text-muted-foreground"
                               title="Archive Asset">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($cars)): ?>
    <div class="p-24 text-center bg-muted/10">
        <div class="w-24 h-24 bg-muted/20 rounded-[2rem] flex items-center justify-center mx-auto mb-8 text-muted-foreground/40">
            <i class="fas fa-box-open text-4xl"></i>
        </div>
        <h4 class="text-lg font-black uppercase tracking-tighter text-foreground mb-2">
            <?php echo !empty($search) || !empty($status_filter) ? 'No Matches Found' : 'Vault Offline'; ?>
        </h4>
        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest max-w-sm mx-auto leading-relaxed">
            <?php echo !empty($search) || !empty($status_filter) 
                ? 'No vehicles match your current filters. Try adjusting your search criteria.' 
                : 'The collection is currently empty. Initiate the acquisition sequence to load inventory.'; ?>
        </p>
        <?php if (empty($search) && empty($status_filter)): ?>
            <a href="add.php" class="inline-block mt-8 text-accent font-black text-[10px] uppercase tracking-widest border-b-2 border-accent pb-1 hover:text-foreground hover:border-foreground transition-all">Forge Asset</a>
        <?php else: ?>
            <a href="index.php" class="inline-block mt-8 text-accent font-black text-[10px] uppercase tracking-widest border-b-2 border-accent pb-1 hover:text-foreground hover:border-foreground transition-all">Clear Filters</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex flex-col sm:flex-row justify-between items-center mt-8 gap-4 px-2">
    <!-- Result Info -->
    <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">
        Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $totalCars); ?> of <?php echo number_format($totalCars); ?> assets
    </div>

    <!-- Pagination Buttons -->
    <div class="flex items-center gap-2">
        <!-- First -->
        <a href="<?php echo buildUrl(['page' => 1]); ?>" 
           class="w-10 h-10 rounded-xl border <?php echo $page <= 1 ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center"
           title="First Page">
            <i class="fas fa-angles-left text-xs"></i>
        </a>

        <!-- Previous -->
        <a href="<?php echo buildUrl(['page' => max(1, $page - 1)]); ?>" 
           class="w-10 h-10 rounded-xl border <?php echo $page <= 1 ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center"
           title="Previous Page">
            <i class="fas fa-chevron-left text-xs"></i>
        </a>

        <!-- Page Numbers -->
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        if ($endPage - $startPage < 4) {
            $startPage = max(1, $endPage - 4);
            $endPage = min($totalPages, $startPage + 4);
        }

        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a href="<?php echo buildUrl(['page' => $i]); ?>" 
               class="w-10 h-10 rounded-xl border <?php echo $i === $page 
                    ? 'border-accent bg-accent text-white shadow-[0_5px_15px_rgba(249,115,22,0.3)]' 
                    : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center font-black text-xs">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <!-- Next -->
        <a href="<?php echo buildUrl(['page' => min($totalPages, $page + 1)]); ?>" 
           class="w-10 h-10 rounded-xl border <?php echo $page >= $totalPages ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center"
           title="Next Page">
            <i class="fas fa-chevron-right text-xs"></i>
        </a>

        <!-- Last -->
        <a href="<?php echo buildUrl(['page' => $totalPages]); ?>" 
           class="w-10 h-10 rounded-xl border <?php echo $page >= $totalPages ? 'border-border/30 text-muted-foreground/30 pointer-events-none' : 'border-border bg-background text-muted-foreground hover:bg-accent hover:border-accent hover:text-white'; ?> transition-all flex items-center justify-center"
           title="Last Page">
            <i class="fas fa-angles-right text-xs"></i>
        </a>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
renderAdminLayout($content, 'Fleet Manager');
?>
