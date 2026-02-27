<?php
/**
 * pages/brand-selection.php
 * Displays all available car makes grouped alphabetically.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Brand Selection - ' . SITE_NAME;
include_once __DIR__ . '/../includes/layout/header.php';

$db = getDB();

// 1. Fetch Popular Brands (Top Row)
$popularStmt = $db->query("SELECT id, name, logo_url FROM makes WHERE is_popular = 1 ORDER BY name");
$popularMakes = $popularStmt->fetchAll();

// 2. Fetch All Brands grouped by first letter
$allStmt = $db->query("SELECT id, name, logo_url FROM makes ORDER BY name");
$allMakes = $allStmt->fetchAll();

// Grouping logic
$groupedMakes = [];
foreach ($allMakes as $make) {
    $firstLetter = strtoupper(substr($make['name'], 0, 1));
    if (!isset($groupedMakes[$firstLetter])) {
        $groupedMakes[$firstLetter] = [];
    }
    $groupedMakes[$firstLetter][] = $make;
}
?>

<div class="min-h-screen bg-white dark:bg-gray-950 pt-32 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="<?php echo url(''); ?>" class="inline-flex items-center text-gray-500 hover:text-accent dark:text-gray-400 dark:hover:text-accent">
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ml-1 font-medium text-gray-900 md:ml-2 dark:text-white">Brand Selection</span>
                    </div>
                </li>
            </ol>
        </nav>

        <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-10">Popular Brands</h1>

        <!-- Popular Brands Grid -->
        <div class="grid grid-cols-4 md:grid-cols-8 lg:grid-cols-12 gap-4 md:gap-6 mb-16">
            <?php foreach ($popularMakes as $make): ?>
            <a href="<?php echo url('cars/' . strtolower(str_replace(' ', '-', $make['name']))); ?>" class="flex flex-col items-center gap-3 group">
                <div class="h-16 w-16 md:h-20 md:w-20 rounded-full bg-gray-50 dark:bg-gray-800 flex items-center justify-center border border-gray-100 dark:border-gray-700 group-hover:shadow-md group-hover:border-accent/30 transition-all p-3 overflow-hidden">
                    <?php if ($make['logo_url']): ?>
                        <img src="<?php echo $make['logo_url']; ?>" alt="<?php echo clean($make['name']); ?>" 
                             class="w-full h-full object-contain group-hover:scale-110 transition-transform dark:invert dark:mix-blend-screen">
                    <?php else: ?>
                        <span class="font-bold text-gray-400 text-xs"><?php echo substr($make['name'], 0, 3); ?></span>
                    <?php endif; ?>
                </div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-accent transition-colors text-center"><?php echo clean($make['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Alphabet Filter Bar -->
        <div class="sticky top-20 z-30 bg-white/90 dark:bg-gray-950/90 backdrop-blur-md py-4 mb-10 border-b border-gray-200 dark:border-gray-800">
            <div class="flex flex-wrap gap-x-4 gap-y-2 justify-start sm:justify-between px-2">
                <?php foreach (range('A', 'Z') as $letter): ?>
                    <?php if (isset($groupedMakes[$letter])): ?>
                        <a href="#letter-<?php echo $letter; ?>" class="font-bold text-gray-900 dark:text-white hover:text-accent transition-colors text-sm sm:text-base">
                            <?php echo $letter; ?>
                        </a>
                    <?php else: ?>
                        <span class="font-bold text-gray-300 dark:text-gray-700 cursor-not-allowed text-sm sm:text-base">
                            <?php echo $letter; ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grouped Brands Directory -->
        <div class="space-y-16">
            <?php foreach (range('A', 'Z') as $letter): ?>
                <?php if (isset($groupedMakes[$letter])): ?>
                    <div id="letter-<?php echo $letter; ?>" class="scroll-mt-36">
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-6 border-b border-gray-200 dark:border-gray-800 pb-2">
                            <?php echo $letter; ?>
                        </h2>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 gap-y-8">
                            <?php foreach ($groupedMakes[$letter] as $make): ?>
                            <a href="<?php echo url('cars/' . strtolower(str_replace(' ', '-', $make['name']))); ?>" class="flex items-center gap-4 group">
                                <div class="w-10 h-10 object-contain flex items-center justify-center shrink-0">
                                    <?php if ($make['logo_url']): ?>
                                        <img src="<?php echo $make['logo_url']; ?>" alt="<?php echo clean($make['name']); ?>" class="w-full h-full object-contain dark:invert dark:mix-blend-screen">
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                            <span class="text-xs font-bold text-gray-400"><?php echo substr($make['name'], 0, 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="font-bold text-sm md:text-base text-gray-800 dark:text-gray-200 group-hover:text-accent transition-colors">
                                    <?php echo clean($make['name']); ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    </div>
</div>



<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>
