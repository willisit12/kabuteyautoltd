<?php
/**
 * includes/component/theme-toggle.php
 * Reusable theme switcher component
 */
function renderThemeToggle($isMobile = false) {
    ?>
    <div class="flex items-center <?php echo $isMobile ? 'w-full' : 'bg-muted/50 border border-border rounded-2xl p-1 gap-1'; ?>" x-data>
        <?php if ($isMobile): ?>
            <button @click="darkMode = !darkMode" class="flex items-center justify-between w-full p-4 rounded-2xl border border-border/50 bg-muted/20 text-foreground font-bold transition-all hover:border-accent/30">
                <div class="flex items-center gap-3">
                    <i class="fas text-accent text-sm" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                    <span class="text-sm" x-text="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"> Theme</span>
                </div>
            </button>
        <?php else: ?>
            <button @click="darkMode = false" 
                    :class="!darkMode ? 'bg-white dark:bg-card text-accent shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    class="w-10 h-10 rounded-xl flex items-center justify-center transition-all"
                    title="Light Mode">
                <i class="fas fa-sun text-sm"></i>
            </button>
            <button @click="darkMode = true" 
                    :class="darkMode ? 'bg-background text-accent shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    class="w-10 h-10 rounded-xl flex items-center justify-center transition-all"
                    title="Dark Mode">
                <i class="fas fa-moon text-sm"></i>
            </button>
        <?php endif; ?>
    </div>
    <?php
}
