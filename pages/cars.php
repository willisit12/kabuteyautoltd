<?php
/**
 * pages/cars.php
 * Inventory Listing Template with Advanced AJAX Filtering
 */

$pageTitle = 'Inventory';

// Load initial data
$makes = getCarMakes();
$bodyTypes = getBodyTypes();

$db = getDB();
$categoryMode = null;
$categoryData = [];

// Determine if we are in a specific category view
if (isset($_GET['make_id']) && !empty($_GET['make_id'])) {
    $stmt = $db->prepare("SELECT name, logo_url FROM makes WHERE id = ?");
    $stmt->execute([$_GET['make_id']]);
    if ($make = $stmt->fetch()) {
        $categoryMode = 'make';
        $categoryData = $make;
        $pageTitle = 'Used ' . $categoryData['name'];
    }
} elseif (isset($_GET['body_type_id']) && !empty($_GET['body_type_id'])) {
    $stmt = $db->prepare("SELECT name, icon_url FROM body_types WHERE id = ?");
    $stmt->execute([$_GET['body_type_id']]);
    if ($type = $stmt->fetch()) {
        $categoryMode = 'type';
        $categoryData = $type;
        $pageTitle = 'Used ' . $categoryData['name'] . 's';
    }
}

include_once __DIR__ . '/../includes/layout/header.php';
?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inventoryApp', () => ({
        resultsHtml: '',
        totalResults: 0,
        loading: false,
        loadingMore: false,
        hasMore: false,
        page: 1,
        sort: 'default',
        activeFilters: {},
        activeFilterLabels: {},

        init() {
            // Listen for filter events coming from car-filter.php
            window.addEventListener('apply-cars-filter', (e) => {
                this.activeFilters = JSON.parse(JSON.stringify(e.detail));
                this.generateFilterLabels();
                this.page = 1;
                this.fetchResults(true);
            });
            
            // Listen for sorting
            this.$watch('sort', () => {
                this.page = 1;
                this.fetchResults(true);
            });
        },

        generateFilterLabels() {
            let labels = {};
            for (const [key, val] of Object.entries(this.activeFilters)) {
                if (!val) continue;
                if (key === 'price_min' && val == 0) continue;
                if (key === 'price_max' && val == 50000) continue;
                if (key === 'mileage_min' && val == 0) continue;
                if (key === 'mileage_max' && val == 200000) continue;
                
                if (Array.isArray(val) && val.length > 0) {
                    labels[key] = val.join(', ');
                } else if (!Array.isArray(val) && val !== '') {
                    if (key.includes('price')) {
                        labels[key] = '$' + (val/1000) + 'k';
                    } else if (key.includes('mileage')) {
                        labels[key] = (val/1000) + 'k mi';
                    } else {
                        labels[key] = val;
                    }
                }
            }
            this.activeFilterLabels = labels;
        },

        async fetchResults(reset = false) {
            if (reset) {
                this.loading = true;
                this.page = 1;
            } else {
                this.loadingMore = true;
            }

            const params = new URLSearchParams();
            Object.keys(this.activeFilters).forEach(key => {
                if (Array.isArray(this.activeFilters[key])) {
                    if (this.activeFilters[key].length > 0) {
                        params.append(key, this.activeFilters[key].join(','));
                    }
                } else if (this.activeFilters[key]) {
                    params.append(key, this.activeFilters[key]);
                }
            });
            // Add sort parameter if not default
            if (this.sort !== 'default') {
                params.append('sort', this.sort);
            }
            
            params.append('page', this.page);
            params.append('_v', Date.now()); // Cache busting

            try {
                const apiPath = `${BASE_URL}/api/get-cars?${params.toString()}`;
                
                // Fetch count first or simultaneously
                const countRes = await fetch(`${apiPath}&count_only=1`);
                if (!countRes.ok) throw new Error('Count fetch failed');
                const countData = await countRes.json();
                this.totalResults = countData.total;

                // Fetch cards
                const response = await fetch(apiPath);
                if (!response.ok) throw new Error('Cards fetch failed');
                const html = await response.text();

                if (reset) {
                    this.resultsHtml = html;
                    this.$nextTick(() => {
                        this.animateCards();
                    });
                } else {
                    this.resultsHtml += html;
                    this.$nextTick(() => {
                        this.animateNewCards();
                    });
                }

                this.hasMore = this.totalResults > (this.page * 8);

            } catch (e) {
                console.error('Inventory fetch error:', e);
                if (reset) this.resultsHtml = '<div class="col-span-full py-20 text-center text-red-500 font-bold">Failed to load inventory. Please try again.</div>';
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },

        loadMore() {
            this.page++;
            this.fetchResults(false);
        },

        animateCards() {
            if (typeof gsap !== 'undefined') {
                const targets = document.querySelectorAll("#inventory-results .car-card");
                if (targets.length === 0) return;

                gsap.fromTo(targets, 
                    { opacity: 0, y: 30 },
                    { 
                        opacity: 1, 
                        y: 0, 
                        duration: 0.8, 
                        stagger: 0.05, 
                        ease: "power4.out",
                        force3D: true,
                        background: 'transparent'
                    }
                );
            }
        },

        animateNewCards() {
            if (typeof gsap !== 'undefined') {
                const targets = document.querySelectorAll(".car-card.opacity-0");
                if (targets.length === 0) return;

                gsap.to(targets, {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    stagger: 0.05,
                    ease: "power2.out"
                });
            }
        },

        hasActiveFilters() {
            return Object.keys(this.activeFilterLabels).length > 0;
        },

        removeFilter(key) {
            window.dispatchEvent(new CustomEvent('remove-car-filter', { detail: { key: key } }));
        }
    }));
});
</script>

<div x-data="inventoryApp()" class="flex flex-col lg:flex-row min-h-screen bg-white dark:bg-gray-950 pt-20 lg:pt-28">
    
    <!-- Filter Component (Sidebar on LG, Drawer on Mobile) -->
    <?php include_once __DIR__ . '/../includes/component/car-filter.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 lg:pl-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Breadcrumbs -->
            <nav class="flex mb-6 text-sm" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="<?php echo url(''); ?>" class="inline-flex items-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors">
                            Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <a href="<?php echo url('cars'); ?>" class="ml-1 text-gray-500 hover:text-gray-900 md:ml-2 dark:text-gray-400 dark:hover:text-white transition-colors">Used Cars</a>
                        </div>
                    </li>
                    <?php if ($categoryMode): ?>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <span class="ml-1 font-medium text-gray-900 md:ml-2 dark:text-white"><?php echo clean($categoryData['name']); ?></span>
                        </div>
                    </li>
                    <?php endif; ?>
                </ol>
            </nav>

            <?php if ($categoryMode): ?>
                <!-- Category Hero Banner -->
                <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 md:p-8 mb-8 shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col md:flex-row items-center gap-6">
                    <div class="w-20 h-20 md:w-24 md:h-24 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center p-3 shrink-0 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <?php if ($categoryMode === 'make' && $categoryData['logo_url']): ?>
                            <img src="<?php echo $categoryData['logo_url']; ?>" alt="<?php echo clean($categoryData['name']); ?>" class="w-full h-full object-contain mix-blend-multiply dark:mix-blend-screen dark:invert">
                        <?php elseif ($categoryMode === 'type' && $categoryData['icon_url']): ?>
                            <img src="<?php echo $categoryData['icon_url']; ?>" alt="<?php echo clean($categoryData['name']); ?>" class="w-full h-full object-contain mix-blend-multiply dark:mix-blend-screen dark:invert">
                        <?php else: ?>
                            <span class="text-3xl font-black text-gray-400 dark:text-gray-500 uppercase"><?php echo substr($categoryData['name'], 0, 1); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-2 tracking-tight">Used <?php echo clean($categoryData['name']); ?></h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm md:text-base leading-relaxed max-w-3xl">
                            <?php if ($categoryMode === 'make'): ?>
                                We offer a diverse range of used <?php echo clean($categoryData['name']); ?> models. Each vehicle undergoes rigorous inspection to ensure cinematic transparency and premium quality.
                            <?php else: ?>
                                Explore our curated collection of premium pre-owned <?php echo clean($categoryData['name']); ?>s, each verified through our rigorous 150+ point inspection process.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Page Header (Only if not category mode) -->
            <?php if (!$categoryMode): ?>
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tighter uppercase mb-2">
                        <?php echo __('Inventory'); ?>
                    </h1>
                </div>
                <!-- Mobile Filter Trigger -->
                <button @click="$dispatch('open-filter')" class="lg:hidden flex items-center gap-3 px-6 py-3 bg-gray-100 dark:bg-gray-800 rounded-2xl font-bold text-gray-900 dark:text-gray-100 active:scale-95 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <?php echo __('Filter'); ?>
                </button>
            </div>
            <?php endif; ?>

            <!-- Results Count & Sorting Utilities -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4 border-b border-gray-100 dark:border-gray-800 pb-4">
                <div class="flex items-center gap-4">
                    <p class="text-gray-900 dark:text-white font-bold text-xl tracking-tight">
                        <span x-text="totalResults">...</span> <span class="text-gray-500 dark:text-gray-400 font-medium uppercase text-sm tracking-widest ml-1">RESULTS</span>
                    </p>
                    
                    <?php if ($categoryMode): ?>
                        <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 hidden md:block"></div>
                        <!-- Mobile Filter Trigger for Category Mode -->
                        <button @click="$dispatch('open-filter')" class="lg:hidden flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg font-bold text-sm text-gray-900 dark:text-gray-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filters
                        </button>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between w-full md:w-auto">
                    <!-- Space for desktop active filters later -->
                    <div class="hidden md:flex flex-wrap gap-2 mr-4" x-show="hasActiveFilters()">
                        <template x-for="(val, key) in activeFilterLabels" :key="key">
                            <!-- Basic active pill format -->
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full text-xs font-medium text-gray-700 dark:text-gray-300">
                                <span class="text-gray-400 capitalize" x-text="key.replace('_id', '').replace('_', ' ') + ':'"></span>
                                <span x-text="val" class="font-bold whitespace-nowrap"></span>
                                <button @click="removeFilter(key)" class="hover:text-red-500 ml-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 font-medium whitespace-nowrap relative" x-data="{ 
                        openSort: false,
                        get currentSortLabel() {
                            const map = {
                                'default': 'Default',
                                'price_asc': 'Price: Low to High',
                                'price_desc': 'Price: High to Low',
                                'newest': 'New Arrivals'
                            };
                            return map[this.sort] || map['default'];
                        }
                    }">
                        <svg class="w-4 h-4 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                        <span class="hidden sm:inline">Sort by</span>
                        
                        <button @click="openSort = !openSort" @click.away="openSort = false" class="flex items-center gap-1 text-gray-900 dark:text-white font-bold cursor-pointer outline-none hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <span x-text="currentSortLabel"></span>
                            <svg class="w-4 h-4 transition-transform" :class="openSort ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        
                        <div x-show="openSort" x-transition class="absolute z-30 top-full right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden" style="display: none;">
                            <?php 
                            $sortOptions = [
                                'default' => 'Default',
                                'price_asc' => 'Price: Low to High',
                                'price_desc' => 'Price: High to Low',
                                'newest' => 'New Arrivals'
                            ];
                            foreach ($sortOptions as $val => $label): ?>
                                <button @click="sort = '<?php echo $val; ?>'; openSort = false" class="block w-full text-left px-4 py-2.5 text-sm transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white" :class="sort === '<?php echo $val; ?>' ? 'font-bold bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white' : ''">
                                    <?php echo $label; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Grid -->
            <div class="relative min-h-[400px]">
                <!-- Loading State (Blur) -->
                <div x-show="loading" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 z-20 backdrop-blur-sm bg-white/30 dark:bg-black/20 flex flex-col items-center justify-center rounded-3xl"
                     style="display: none;">
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-full shadow-2xl border border-gray-100 dark:border-gray-700">
                        <svg class="w-12 h-12 text-gray-900 dark:text-white animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="mt-4 font-bold text-gray-900 dark:text-white uppercase tracking-widest text-xs"><?php echo __('Finding Perfection...'); ?></p>
                </div>

                <!-- Cards Grid -->
                <div id="inventory-results" 
                     class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 transition-all duration-500"
                     :class="loading ? 'grayscale opacity-50 contrast-50' : ''"
                     x-html="resultsHtml">
                    <!-- Cards injected via AJAX -->
                     <div class="col-span-full py-20 text-center">
                        <div class="animate-pulse flex flex-col items-center">
                            <div class="h-12 w-12 bg-gray-200 dark:bg-gray-800 rounded-full mb-4"></div>
                            <div class="h-4 w-48 bg-gray-200 dark:bg-gray-800 rounded-full"></div>
                        </div>
                     </div>
                </div>

                <!-- Load More / Pagination -->
                <div x-show="hasMore" class="mt-16 text-center" style="display: none;">
                    <button @click="loadMore()" 
                            :disabled="loadingMore"
                            class="group relative inline-flex items-center gap-3 px-10 py-5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-gray-800 dark:hover:bg-gray-200 transition-all overflow-hidden shadow-2xl">
                        <span class="relative z-10" x-text="loadingMore ? '<?php echo __('Loading...'); ?>' : '<?php echo __('Load More Vehicles'); ?>'"></span>
                        <svg x-show="!loadingMore" class="w-4 h-4 relative z-10 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>


