<?php
/**
 * includes/layout/admin-layout.php
 * Shared layout wrapper for Admin Elite panel
 */

require_once __DIR__ . '/../auth.php';
requireAuth();
require_once __DIR__ . '/../component/preloader.php';

$user = getUserInfo();

function renderAdminLayout($content, $pageTitle = 'Dashboard') {
    global $user;
    ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Elite</title>
    
    <!-- Theme Token Initialization -->
    <style>
        :root {
            --background-rgb: 255 255 255;
            --foreground-rgb: 2 6 23;
            --accent: #f97316;
            --muted-rgb: 241 245 249;
            --muted-foreground: #64748b;
            --border-rgb: 226 232 240;
            --card-rgb: 255 255, 255;
            --sidebar: #0f172a;
            
            /* Calculated variables for standard non-Tailwind use */
            --background: rgb(var(--background-rgb));
            --foreground: rgb(var(--foreground-rgb));
            --muted: rgb(var(--muted-rgb));
            --border: rgb(var(--border-rgb));
            --card: rgb(var(--card-rgb));
        }
        .dark {
            --background-rgb: 2 6 23;
            --foreground-rgb: 248 250 252;
            --accent: #f97316;
            --muted-rgb: 30 41 59;
            --muted-foreground: #94a3b8;
            --border-rgb: 30 41 59;
            --card-rgb: 15 23 42;
            --sidebar: #020617;
        }
    </style>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: 'rgb(var(--background-rgb) / <alpha-value>)',
                        foreground: 'rgb(var(--foreground-rgb) / <alpha-value>)',
                        accent: 'var(--accent)',
                        muted: { 
                            DEFAULT: 'rgb(var(--muted-rgb) / <alpha-value>)',
                            foreground: 'var(--muted-foreground)' 
                        },
                        border: 'rgb(var(--border-rgb) / <alpha-value>)',
                        card: 'rgb(var(--card-rgb) / <alpha-value>)',
                        sidebar: 'var(--sidebar)'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--background);
            color: var(--foreground);
            margin: 0;
        }
        h1, h2, h3, .font-heading { font-family: 'Outfit', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.3);
        }
        .sidebar-link.active {
            background-color: var(--accent);
            color: white !important;
            box-shadow: 0 10px 20px -5px rgba(249, 115, 22, 0.4);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        [x-cloak] { display: none !important; }
        
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        /* Global Admin Input Styling for High Contrast */
        input, select, textarea {
            background-color: var(--card) !important;
            color: var(--foreground) !important;
            border: 1px solid var(--border) !important;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--accent) !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgb(249 115 22 / 20%) !important;
        }
        ::placeholder {
            color: var(--muted-foreground) !important;
            opacity: 0.6 !important;
        }

        /* Essential Dark Mode Overrides to prevent "Flash of White" */
        .dark body, .dark .bg-background {
            background-color: var(--background) !important;
        }
        .dark .text-foreground {
            color: var(--foreground) !important;
        }
    </style>
    
    <script>
        // Unify storage key to 'admin-theme' for both head script and Alpine.js
        if (localStorage.getItem('admin-theme') === 'true' || localStorage.getItem('admin-theme') === '"true"' || (!('admin-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <?php renderPreloaderCSS(); ?>
</head>
<body class="h-full bg-background transition-colors duration-500 overflow-hidden" 
      x-data="{ 
          mobileMenu: false, 
          sidebarCollapsed: $persist(false).as('sidebar-collapsed'),
          darkMode: $persist(document.documentElement.classList.contains('dark')).as('admin-theme')
      }"
      x-init="$watch('darkMode', val => {
          if (val) document.documentElement.classList.add('dark');
          else document.documentElement.classList.remove('dark');
      })">

    <!-- Global Preloader -->
    <?php renderPreloaderHTML(); ?>
    
    <div class="flex h-full">
        <!-- Sidebar Backdrop (Mobile Only) -->
        <div x-show="mobileMenu" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenu = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] lg:hidden"
             x-cloak></div>

        <!-- Sidebar Content -->
        <aside :class="{
                   'translate-x-0 shadow-2xl': mobileMenu,
                   '-translate-x-full lg:translate-x-0': !mobileMenu,
                   'lg:w-24': sidebarCollapsed && !mobileMenu,
                   'lg:w-72': !sidebarCollapsed || mobileMenu,
                   'w-72': true
               }"
               class="fixed lg:static top-0 left-0 h-full bg-sidebar text-white flex flex-col z-[100] transition-all duration-500 ease-in-out border-r border-white/5 -translate-x-full lg:translate-x-0"
               x-cloak>
            
            <div class="p-8 pb-4 flex justify-between items-center overflow-hidden">
                <a href="<?php echo url('admin/dashboard'); ?>" class="flex items-center gap-3 group whitespace-nowrap">
                    <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform flex-shrink-0">
                        <i class="fas fa-car text-xl"></i>
                    </div>
                    <div x-show="!sidebarCollapsed || mobileMenu" x-transition.opacity>
                        <h1 class="font-black tracking-tighter uppercase text-lg leading-none"><?php echo SITE_NAME; ?></h1>
                        <p class="text-[8px] font-black uppercase tracking-[0.3em] text-accent mt-1">Admin Elite</p>
                    </div>
                </a>
                <button @click="mobileMenu = false" class="lg:hidden text-white/40 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Collapse Toggle (Desktop Only) -->
            <div class="hidden lg:flex justify-end px-6 pt-2">
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-all">
                    <i class="fas" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                </button>
            </div>

            <nav class="flex-1 px-6 py-8 space-y-2 overflow-y-auto custom-scrollbar overflow-x-hidden">
                <p x-show="!sidebarCollapsed || mobileMenu" class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-4 ml-2" x-transition.opacity>Main Console</p>
                <div x-show="sidebarCollapsed && !mobileMenu" class="h-px bg-white/5 mb-6" x-transition.opacity></div>
                
                <?php
                $current_uri = $_SERVER['REQUEST_URI'];
                function navLink($path, $label, $icon, $current_uri) {
                    $isActive = strpos($current_uri, $path) !== false;
                    $class = $isActive ? 'active' : 'text-white/60 hover:text-white hover:bg-white/5';
                    ?>
                    <a href="<?php echo url('admin/' . $path); ?>" 
                       class="sidebar-link flex items-center gap-4 px-5 py-4 rounded-2xl transition-all group <?php echo $class; ?>"
                       :title="sidebarCollapsed ? '<?php echo $label; ?>' : ''">
                        <i class="fas <?php echo $icon; ?> w-5 group-hover:scale-110 transition-transform flex-shrink-0"></i>
                        <span x-show="!sidebarCollapsed || mobileMenu" class="font-bold text-sm tracking-tight whitespace-nowrap" x-transition.opacity><?php echo $label; ?></span>
                    </a>
                    <?php
                }

                navLink('dashboard.php', 'Overview', 'fa-th-large', $current_uri);
                navLink('cars/', 'Fleet Management', 'fa-car', $current_uri);
                navLink('cars/import.php', 'Bulk Import', 'fa-file-import', $current_uri);
                navLink('orders/', 'Acquisitions', 'fa-shopping-bag', $current_uri);
                navLink('inquiries/', 'Dialogue Center', 'fa-comments', $current_uri);
                ?>

                <p x-show="!sidebarCollapsed || mobileMenu" class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-4 ml-2 pt-6" x-transition.opacity>Security & Identity</p>
                <div x-show="sidebarCollapsed && !mobileMenu" class="h-px bg-white/5 my-6" x-transition.opacity></div>
                <?php
                navLink('users/', 'Access Control', 'fa-users', $current_uri);
                navLink('change-password.php', 'Vault Security', 'fa-key', $current_uri);
                ?>

                <p x-show="!sidebarCollapsed || mobileMenu" class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-4 ml-2 pt-6" x-transition.opacity>External</p>
                <div x-show="sidebarCollapsed && !mobileMenu" class="h-px bg-white/5 my-6" x-transition.opacity></div>
                <a href="<?php echo url(); ?>" target="_blank" class="sidebar-link flex items-center gap-4 px-5 py-4 rounded-2xl text-white/60 hover:text-white hover:bg-white/5 transition-all group" :title="sidebarCollapsed ? 'View Website' : ''">
                    <i class="fas fa-external-link-alt w-5 group-hover:scale-110 transition-transform flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed || mobileMenu" class="font-bold text-sm tracking-tight whitespace-nowrap" x-transition.opacity>View Website</span>
                </a>
            </nav>

            <div class="p-6 border-t border-white/5">
                <a href="<?php echo url('admin/logout.php'); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl text-red-100/50 hover:bg-red-500/10 hover:text-red-400 transition-all font-bold text-sm tracking-tight group" :title="sidebarCollapsed ? 'Terminate Session' : ''">
                    <i class="fas fa-power-off w-5 group-hover:rotate-12 transition-transform flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed || mobileMenu" class="whitespace-nowrap" x-transition.opacity>Terminate Session</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 bg-background relative overflow-hidden transition-all duration-500">
            <!-- Header -->
            <header class="h-24 border-b border-border/50 flex items-center justify-between px-8 bg-background/80 backdrop-blur-md z-40">
                <div class="flex items-center gap-6">
                    <button @click="mobileMenu = true" 
                            class="lg:hidden w-12 h-12 rounded-2xl bg-muted/50 border border-border flex items-center justify-center text-foreground hover:bg-muted transition-all">
                        <i class="fas fa-bars-staggered text-xl"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-black tracking-tighter uppercase text-foreground leading-none"><?php echo $pageTitle; ?></h2>
                        <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mt-1 hidden sm:block">Admin Elite Console</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Quick Theme Config -->
                    <div class="flex items-center bg-muted/50 border border-border rounded-2xl p-1 gap-1">
                        <button @click="darkMode = false" 
                                :class="!darkMode ? 'bg-white text-accent shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                            <i class="fas fa-sun text-sm"></i>
                        </button>
                        <button @click="darkMode = true" 
                                :class="darkMode ? 'bg-background text-accent shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                            <i class="fas fa-moon text-sm"></i>
                        </button>
                    </div>

                    <div class="h-8 w-[1px] bg-border/50 mx-2 hidden sm:block"></div>

                    <!-- User Identity -->
                    <div class="flex items-center gap-4 group cursor-pointer relative">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-black text-foreground leading-none mb-1"><?php echo $user ? clean($user['name']) : 'Guest'; ?></p>
                            <span class="text-[8px] font-black uppercase tracking-[0.2em] px-2 py-0.5 bg-accent/10 text-accent rounded-full border border-accent/20">
                                <?php echo $user ? strtoupper((string)$user['role']) : 'GUEST'; ?>
                            </span>
                        </div>
                        <div class="w-12 h-12 bg-accent/10 border border-accent/20 rounded-2xl flex items-center justify-center text-accent font-black text-xl shadow-lg group-hover:scale-105 group-hover:rotate-3 transition-all duration-500 overflow-hidden relative">
                            <?php if ($user && !empty($user['avatar_url'])): ?>
                                <img src="<?php echo $user['avatar_url']; ?>" alt="Avatar" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span><?php echo $user ? substr($user['name'], 0, 1) : '?'; ?></span>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-6 md:p-12 relative">
                <div class="max-w-[1600px] mx-auto relative z-10 reveal-content">
                    <?php echo $content; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Professional GSAP Entrances
        document.addEventListener('DOMContentLoaded', () => {
             gsap.from(".reveal-content", {
                y: 40,
                opacity: 0,
                duration: 1.4,
                ease: "expo.out",
                delay: 0.2
            });
        });
    </script>
    <?php renderPreloaderJS(); ?>
</body>
</html>
<?php
}
?>
