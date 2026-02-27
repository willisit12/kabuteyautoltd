<?php
/**
 * includes/component/car-filter.php
 * Professional Advanced Filtering Component for Desktop Sidebar and Mobile Drawer
 */

?>

<script>
function carFilter() {
    return {
        openMobileFilter: false,
        totalResults: 0,
        filters: {
            make_id: '',
            body_type_id: '',
            price_min: 0,
            price_max: 50000,
            mileage_min: 0,
            mileage_max: 200000,
            year_from: '',
            year_to: '',
            transmission: [],
            fuel_type: [],
            search: ''
        },

        init() {
            // Read URL params and set filters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('make_id')) this.filters.make_id = urlParams.get('make_id');
            if (urlParams.has('body_type_id')) this.filters.body_type_id = urlParams.get('body_type_id');
            if (urlParams.has('search')) this.filters.search = urlParams.get('search');
            if (urlParams.has('condition')) this.filters.condition = urlParams.get('condition');
            
            this.updateResultCount();
            
            // Dispatch initial filters to the grid so it loads with URL params applied
            setTimeout(() => { this.applyFilters(); }, 50);
            
            // Watch for filter changes to update count auto (for mobile button)
            this.$watch('filters', () => {
                this.updateResultCount();
                // If on desktop, apply auto
                if (window.innerWidth >= 1024) {
                    this.applyFilters();
                }
            }, { deep: true });
        },

        removeSingleFilter(key) {
             if (Array.isArray(this.filters[key])) {
                  this.filters[key] = [];
             } else if (key === 'price_min' || key === 'price_max') {
                  this.filters.price_min = 0;
                  this.filters.price_max = 50000;
             } else if (key === 'mileage_min' || key === 'mileage_max') {
                  this.filters.mileage_min = 0;
                  this.filters.mileage_max = 200000;
             } else {
                  this.filters[key] = '';
             }
             // Force apply immediately when a pill is removed, even on mobile
             this.applyFilters();
        },

        async updateResultCount() {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) params.append(key, this.filters[key]);
            });
            params.append('count_only', '1');
            params.append('_v', Date.now()); // Cache busting

            try {
                const response = await fetch(`${BASE_URL}/api/get-cars?${params.toString()}`);
                if (!response.ok) throw new Error('Count fetch failed');
                const data = await response.json();
                this.totalResults = data.total;
            } catch (e) {
                console.error('Count update failed', e);
            }
        },

        applyFilters() {
            // Dispatch event to inventory grid
            window.dispatchEvent(new CustomEvent('apply-cars-filter', { 
                detail: this.filters 
            }));
        },

        resetFilters() {
            this.filters = {
                make_id: '',
                body_type_id: '',
                price_min: 0,
                price_max: 50000,
                mileage_min: 0,
                mileage_max: 200000,
                year_from: '',
                year_to: '',
                transmission: [],
                fuel_type: [],
                search: ''
            };
        },

        formatRange(min, max, unit = '') {
            if (min == 0 && max == 50000) return 'Any';
            return `${min / 1000}${unit} ~ ${max / 1000}${unit}`;
        }
    }
}
</script>

<div x-data="carFilter()" 
     class="relative" 
     @open-filter.window="openMobileFilter = true"
     @reset-filters.window="resetFilters()">
    
    <!-- Desktop Sidebar Sidebar -->
    <aside class="hidden lg:block w-72 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 h-[calc(100vh-6rem)] sticky top-24 overflow-y-auto" style="max-height: calc(100vh - 6rem);" data-lenis-prevent>
        <div class="p-6 pb-32">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?php echo __('Filter'); ?></h2>
                <button @click="resetFilters()" class="text-sm font-medium text-gray-500 hover:text-primary transition-colors">
                    <?php echo __('Reset'); ?>
                </button>
            </div>

            <!-- Filter Content (Shared) -->
            <div class="space-y-8">
                <?php renderFilterContent('desktop'); ?>
            </div>
        </div>
    </aside>

    <!-- Mobile Drawer -->
    <template x-if="true">
        <div x-show="openMobileFilter" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-0 z-50 lg:hidden bg-white dark:bg-gray-900 overflow-y-auto"
             style="display: none;" data-lenis-prevent>
            
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-800 sticky top-0 bg-white dark:bg-gray-900 z-10">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo __('Filter'); ?></h2>
                    <button @click="openMobileFilter = false" class="p-2 text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 p-4 pb-24 space-y-8">
                    <?php renderFilterContent('mobile'); ?>
                </div>

                <!-- Footer button -->
                <div class="fixed bottom-0 left-0 right-0 p-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 flex gap-4">
                    <button @click="resetFilters()" class="flex-1 py-4 text-gray-900 dark:text-white font-bold border border-gray-900 dark:border-white rounded-full transition-colors">
                        <?php echo __('Reset'); ?>
                    </button>
                    <button @click="applyFilters(); openMobileFilter = false" class="flex-[2] py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-full transition-transform active:scale-95 shadow-lg shadow-gray-200 dark:shadow-none">
                        <?php echo __('View'); ?> (<span x-text="totalResults">...</span>)
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<?php
/**
 * Internal helper to render the shared filter fields
 */
function renderFilterContent($prefix = 'desktop') {
    static $makes, $bodyTypes, $years, $fuelTypes, $transmissions, $seats, $groupedMakes;
    
    if ($makes === null) {
        $makes = getCarMakes() ?: [];
        $bodyTypes = getBodyTypes() ?: [];
        $years = range(date('Y'), 2000);
        $fuelTypes = ['Gasoline', 'Diesel', 'Hybrid', 'Electric'];
        $transmissions = ['Automatic', 'Manual', 'CVT', 'DCT'];
        $seats = range(2, 9);
        
        $groupedMakes = [];
        foreach ($makes as $make) {
            if (empty($make['name'])) continue;
            $firstLetter = strtoupper(substr($make['name'], 0, 1));
            if (!isset($groupedMakes[$firstLetter])) {
                $groupedMakes[$firstLetter] = [];
            }
            $groupedMakes[$firstLetter][] = $make;
        }
        ksort($groupedMakes);
    }
    ?>
    <!-- Make & Model -->
    <div class="space-y-4">
        <h3 class="font-bold text-gray-900 dark:text-white"><?php echo __('Make & Model'); ?></h3>
        <div class="space-y-3">
            <script>
                window.carMakesData = window.carMakesData || <?php echo json_encode($makes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            </script>
            <div x-data="{ 
                openMake: false, 
                get selectedMakeName() {
                    const makeId = this.filters.make_id;
                    if (!makeId) return '<?php echo __('Any'); ?>';
                    const make = window.carMakesData.find(m => m.id == makeId);
                    return make ? make.name : '<?php echo __('Any'); ?>';
                },
                get selectedMakeLogo() {
                    const makeId = this.filters.make_id;
                    if (!makeId) return null;
                    const make = window.carMakesData.find(m => m.id == makeId);
                    return make ? make.logo_url : null;
                },
                scrollToLetter(letter) {
                    const el = document.getElementById('make-group-<?php echo $prefix; ?>-' + letter);
                    if (el) {
                        const container = document.getElementById('make-scroll-<?php echo $prefix; ?>');
                        if (container) {
                            container.scrollTo({ top: el.offsetTop - container.offsetTop, behavior: 'smooth' });
                        } else {
                            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                }
            }" class="relative">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block"><?php echo __('Make'); ?></label>
                
                <button @click="openMake = !openMake" @click.away="openMake = false" type="button" class="w-full h-12 px-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100 focus:border-transparent outline-none transition-all flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <template x-if="selectedMakeLogo">
                            <img :src="selectedMakeLogo" class="w-6 h-6 object-contain mix-blend-multiply dark:filter dark:brightness-0 dark:invert" alt="">
                        </template>
                        <template x-if="!selectedMakeLogo && filters.make_id">
                             <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                  <span class="text-[10px] font-bold text-gray-500 dark:text-gray-300" x-text="selectedMakeName.substring(0,1)"></span>
                             </div>
                        </template>
                        <span x-text="selectedMakeName" class="font-medium"></span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openMake ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <!-- Custom Make Dropdown Panel -->
                <div x-show="openMake" x-transition.opacity.duration.200ms
                     class="absolute z-50 top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden flex h-80"
                     style="display: none;" data-lenis-prevent>
                    
                    <!-- Scrollable Brands List -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-6 relative thin-scrollbar pr-8 scroll-smooth" id="make-scroll-<?php echo $prefix; ?>">
                        <!-- 'Any Make' Option -->
                        <button @click="filters.make_id = ''; openMake = false" 
                                class="w-full flex items-center gap-4 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left"
                                :class="filters.make_id === '' ? 'bg-gray-50 dark:bg-gray-700 font-bold' : ''">
                            <span class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 font-medium">A</span>
                            <span class="text-gray-900 dark:text-white">All Makes</span>
                        </button>

                        <?php foreach ($groupedMakes as $letter => $groupMakes): ?>
                            <div id="make-group-<?php echo $prefix; ?>-<?php echo $letter; ?>" class="scroll-mt-4">
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 pl-2"><?php echo $letter; ?></div>
                                <div class="space-y-1">
                                    <?php foreach ($groupMakes as $make): ?>
                                        <button @click="filters.make_id = '<?php echo $make['id']; ?>'; openMake = false" 
                                                class="w-full flex items-center gap-4 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left group"
                                                :class="filters.make_id == '<?php echo $make['id']; ?>' ? 'bg-gray-50 dark:bg-gray-700/50' : ''">
                                            
                                            <div class="w-8 h-8 flex items-center justify-center shrink-0">
                                                <?php if ($make['logo_url']): ?>
                                                    <img src="<?php echo $make['logo_url']; ?>" alt="" class="w-full h-full object-contain mix-blend-multiply dark:filter dark:brightness-0 dark:invert opacity-70 group-hover:opacity-100 transition-opacity" :class="filters.make_id == '<?php echo $make['id']; ?>' ? 'opacity-100' : ''">
                                                <?php else: ?>
                                                     <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center">
                                                          <span class="text-xs font-bold text-gray-500 dark:text-gray-400"><?php echo substr($make['name'], 0, 1); ?></span>
                                                     </div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200 group-hover:text-gray-900 dark:group-hover:text-white transition-colors"
                                                  :class="filters.make_id == '<?php echo $make['id']; ?>' ? 'text-gray-900 dark:text-white font-bold' : ''">
                                                <?php echo clean($make['name']); ?>
                                            </span>
                                            
                                            <div x-show="filters.make_id == '<?php echo $make['id']; ?>'" class="ml-auto text-gray-900 dark:text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- A-Z Sidebar Navbar -->
                    <div class="w-6 absolute right-0 top-0 bottom-0 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-l border-gray-100 dark:border-gray-700 flex flex-col justify-between py-4 items-center z-10 text-[10px] font-bold text-gray-400">
                        <?php foreach (range('A', 'Z') as $letter): ?>
                            <?php if (isset($groupedMakes[$letter])): ?>
                                <button type="button" @click.stop="scrollToLetter('<?php echo $letter; ?>')" class="hover:text-gray-900 dark:hover:text-white transition-colors w-full text-center leading-none py-px"><?php echo $letter; ?></button>
                            <?php else: ?>
                                <span class="opacity-30 cursor-default leading-none py-px"><?php echo $letter; ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block"><?php echo __('Search'); ?></label>
                <input x-model.debounce.500ms="filters.search" type="text" placeholder="<?php echo __('Model, Keyword...'); ?>" 
                       class="w-full h-12 px-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gray-900 dark:focus:ring-gray-100 focus:border-transparent outline-none transition-all placeholder:text-gray-400">
            </div>
        </div>
    </div>

    <!-- Pricing Range (Custom Slider) -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white"><?php echo __('Price (grand)'); ?></h3>
            <span class="text-xs font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded" x-text="formatRange(filters.price_min, filters.price_max, 'K')"></span>
        </div>
        <div class="px-2">
             <div class="relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full">
                <input type="range" x-model="filters.price_min" min="0" max="50000" step="1000" class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none accent-gray-900 dark:accent-white slider-thumb">
                <input type="range" x-model="filters.price_max" min="0" max="50000" step="1000" class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none accent-gray-900 dark:accent-white slider-thumb">
             </div>
             <div class="flex justify-between mt-4">
                <!-- Custom Price Min Dropdown -->
                <div x-data="{ openPMin: false }" class="relative w-24">
                    <button @click="openPMin = !openPMin" @click.away="openPMin = false" class="w-full flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-800 border box-border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 pl-2 pr-2 text-gray-700 dark:text-gray-300 font-medium">
                        <span x-text="filters.price_min > 0 ? '$' + filters.price_min/1000 + 'k' : '<?php echo __('Min'); ?>'"></span>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openPMin" x-transition class="absolute z-20 top-full left-0 mt-1 w-24 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" style="display: none;">
                        <?php foreach ([0=>'Min', 5000=>'$5k', 10000=>'$10k', 20000=>'$20k', 30000=>'$30k'] as $val => $label): ?>
                            <button @click="filters.price_min = <?php echo $val; ?>; openPMin = false" class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                                <?php echo $label; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Custom Price Max Dropdown -->
                <div x-data="{ openPMax: false }" class="relative w-24">
                    <button @click="openPMax = !openPMax" @click.away="openPMax = false" class="w-full flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-800 border box-border border-gray-200 dark:border-gray-700 rounded-lg py-1.5 pl-2 pr-2 text-gray-900 dark:text-white font-bold">
                        <span x-text="filters.price_max < 50000 ? '$' + filters.price_max/1000 + 'k' : '<?php echo __('Max'); ?>'"></span>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openPMax" x-transition class="absolute z-20 top-full right-0 mt-1 w-24 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" style="display: none;">
                        <?php foreach ([20000=>'$20k', 30000=>'$30k', 40000=>'$40k', 50000=>'Max'] as $val => $label): ?>
                            <button @click="filters.price_max = <?php echo $val; ?>; openPMax = false" class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                                <?php echo $label; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
             </div>
        </div>
    </div>

    <!-- Body Type -->
    <div x-data="{ open: true }" class="border-t border-gray-100 dark:border-gray-800 pt-6">
        <button @click="open = !open" class="flex items-center justify-between w-full mb-4">
            <h3 class="font-bold text-gray-900 dark:text-white"><?php echo __('Body Type'); ?></h3>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <div class="grid grid-cols-1 gap-3">
                <?php foreach (array_slice($bodyTypes ?: [], 0, 4) as $type): ?>
                    <label class="relative flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-900 dark:hover:border-gray-100 cursor-pointer transition-all group"
                           :class="filters.body_type_id == <?php echo $type['id']; ?> ? 'bg-gray-50 dark:bg-gray-800/50 border-gray-900 dark:border-gray-100 ring-1 ring-gray-900 dark:ring-gray-100' : 'bg-white dark:bg-gray-800'">
                        <input type="radio" x-model="filters.body_type_id" value="<?php echo $type['id']; ?>" class="hidden">
                        <?php if ($type['icon_url']): ?>
                            <img src="<?php echo $type['icon_url']; ?>" class="w-12 h-8 object-contain mr-4 mix-blend-multiply dark:filter dark:brightness-0 dark:invert" alt="">
                        <?php endif; ?>
                        <span class="font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors"
                              :class="filters.body_type_id == <?php echo $type['id']; ?> ? 'text-gray-900 dark:text-white font-bold' : ''"><?php echo $type['name']; ?></span>
                        <div x-show="filters.body_type_id == <?php echo $type['id']; ?>" class="ml-auto">
                            <svg class="w-5 h-5 text-gray-900 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="mt-4 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-bold text-sm flex items-center gap-1 group transition-colors">
                <?php echo __('Show more'); ?>
                <svg class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
    </div>

    <!-- More collapsible sections (Transmission, Fuel, etc.) -->
    <?php 
    renderCollapsibleFilter(__('Condition'), 'condition', ['New', 'Used', 'Certified']);
    renderCollapsibleFilter(__('Transmission'), 'transmission', $transmissions ?: []);
    renderCollapsibleFilter(__('Fuel Type'), 'fuel_type', $fuelTypes ?: []);
    ?>

    <?php
}

function renderCollapsibleFilter($title, $key, $options) {
    $safeOptions = is_array($options) ? $options : [];
    ?>
    <div x-data="{ open: false }" class="border-t border-gray-100 dark:border-gray-800 pt-6">
        <button @click="open = !open" class="flex items-center justify-between w-full">
            <h3 class="font-bold text-gray-900 dark:text-white"><?php echo $title; ?></h3>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse class="mt-4 grid grid-cols-1 gap-2">
            <?php foreach ($safeOptions as $opt): ?>
                <label class="flex items-center gap-3 cursor-pointer py-1.5 group">
                    <input type="checkbox" x-model="filters.<?php echo $key; ?>" value="<?php echo $opt; ?>" class="w-4 h-4 rounded text-gray-900 dark:text-white focus:ring-gray-900 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors"><?php echo $opt; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<style>
/* Custom thumb styling for overlapping range sliders */
.slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    pointer-events: auto;
}
.slider-thumb::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #111827; /* gray-900 */
    cursor: pointer;
    border: 3px solid white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.dark .slider-thumb::-webkit-slider-thumb {
    background: #ffffff;
    border-color: #111827;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
/* Thin styled scrollbar for make dropdown */
.thin-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.thin-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.thin-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.15);
    border-radius: 10px;
}
.dark .thin-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.15);
}
.thin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.15) transparent;
}
</style>
