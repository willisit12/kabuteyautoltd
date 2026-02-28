<?php
/**
 * includes/layout/customer-layout.php
 * Shared layout wrapper for Member Intelligence Portal
 */

require_once __DIR__ . '/../auth.php';
requireAuth();
require_once __DIR__ . '/../component/preloader.php';

$user = getUserInfo();

function renderCustomerLayout($content, $pageTitle = 'Intelligence Portal') {
    global $user;
    $current_uri = $_SERVER['REQUEST_URI'];
    ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Inner Circle</title>
    
    <style>
        :root {
            --background-rgb: 255 255 255;
            --foreground-rgb: 2 6 23;
            --accent: #f97316;
            --muted-rgb: 241 245 249;
            --muted-foreground: #64748b;
            --border-rgb: 226 232 240;
            --sidebar: #0f172a;
            
            --background: rgb(var(--background-rgb));
            --foreground: rgb(var(--foreground-rgb));
            --muted: rgb(var(--muted-rgb));
            --border: rgb(var(--border-rgb));
        }
        .dark {
            --background-rgb: 2 6 23;
            --foreground-rgb: 248 250 252;
            --accent: #f97316;
            --muted-rgb: 30 41 59;
            --muted-foreground: #94a3b8;
            --border-rgb: 30 41 59;
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
                        sidebar: 'var(--sidebar)'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--background); color: var(--foreground); margin:0;}
        h1, h2, h3, .font-heading { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .dark .glass { background: rgba(15, 23, 42, 0.3); }
        .sidebar-link.active { background-color: var(--accent); color: white !important; box-shadow: 0 10px 20px -5px rgba(249, 115, 22, 0.4); }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    </style>
    <script>
        if (localStorage.getItem('customer-theme') === 'true' || (!('customer-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
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
          sidebarCollapsed: $persist(false).as('customer-sidebar-collapsed'),
          darkMode: $persist(document.documentElement.classList.contains('dark')).as('customer-theme')
      }"
      x-init="$watch('darkMode', val => {
          if (val) document.documentElement.classList.add('dark');
          else document.documentElement.classList.remove('dark');
      })">

    <?php renderPreloaderHTML(); ?>

    <div class="flex h-full">
        <!-- Sidebar Backdrop -->
        <div x-show="mobileMenu" @click="mobileMenu = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] lg:hidden" x-cloak></div>

        <!-- Sidebar -->
        <aside :class="[
                   mobileMenu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                   sidebarCollapsed ? 'lg:w-24' : 'lg:w-72'
               ]"
               class="fixed lg:static top-0 left-0 h-full bg-sidebar text-white flex flex-col z-[100] transition-all duration-500 ease-in-out border-r border-white/5">
            
            <div class="p-8 flex items-center transition-all duration-500" :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
                <a href="<?php echo url(); ?>" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-12 transition-transform flex-shrink-0">
                        <i class="fas fa-crown text-xl"></i>
                    </div>
                    <div x-show="!sidebarCollapsed" x-transition.opacity class="transition-all duration-500">
                        <h1 class="font-black tracking-tighter uppercase text-lg leading-none">Inner Circle</h1>
                        <p class="text-[8px] font-black uppercase tracking-[0.3em] text-accent mt-1">Intelligence Portal</p>
                    </div>
                </a>
                
                <!-- Toggle (LG only) -->
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden lg:flex w-8 h-8 rounded-lg bg-white/5 items-center justify-center text-white/40 hover:text-white hover:bg-white/10 transition-all">
                    <i class="fas transition-transform duration-500" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                </button>

                <button @click="mobileMenu = false" class="lg:hidden text-white/40 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 px-6 py-8 space-y-2 overflow-y-auto custom-scrollbar">
                <p class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-4 ml-2 transition-all" x-show="!sidebarCollapsed" x-transition.opacity>Member Terminal</p>
                
                <?php
                function navItem($url_path, $label, $icon, $current_uri) {
                    $isActive = (strpos($current_uri, $url_path) !== false);
                    $url = url($url_path);
                    $class = $isActive ? 'active' : 'text-white/60 hover:text-white hover:bg-white/5';
                    ?>
                    <a href="<?php echo $url; ?>" class="sidebar-link flex items-center gap-4 px-5 py-4 rounded-2xl transition-all group <?php echo $class; ?>"
                       :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                        <i class="fas <?php echo $icon; ?> w-5 group-hover:scale-110 transition-transform flex-shrink-0"></i>
                        <span class="font-bold text-sm tracking-tight" x-show="!sidebarCollapsed" x-transition.opacity><?php echo $label; ?></span>
                    </a>
                    <?php
                }

                navItem('dashboard', 'Intelligence Overview', 'fa-th-large', $current_uri);
                navItem('customer/orders', 'Acquisitions', 'fa-shopping-bag', $current_uri);
                navItem('customer/favorites', 'Wishlist', 'fa-heart', $current_uri);
                navItem('customer/inquiries', 'Correspondence', 'fa-envelope', $current_uri);
                navItem('customer/profile', 'Identity Profile', 'fa-user-circle', $current_uri);
                ?>
            </nav>

            <div class="p-6 border-t border-white/5 whitespace-nowrap overflow-hidden">
                <a href="<?php echo url('logout'); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl text-red-100/50 hover:bg-red-500/10 hover:text-red-400 transition-all font-bold text-sm tracking-tight group"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i class="fas fa-power-off w-5 group-hover:rotate-12 transition-transform flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition.opacity>Terminate Session</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 bg-background relative overflow-hidden transition-all duration-500">
            <header class="h-24 border-b border-border/50 flex items-center justify-between px-8 bg-background/80 backdrop-blur-md z-40">
                <div class="flex items-center gap-6">
                    <button @click="mobileMenu = true" class="lg:hidden w-12 h-12 rounded-2xl bg-muted/50 border border-border flex items-center justify-center text-foreground hover:bg-muted transition-all">
                        <i class="fas fa-bars-staggered text-xl"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-black tracking-tighter uppercase text-foreground leading-none"><?php echo $pageTitle; ?></h2>
                        <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mt-1 hidden sm:block">Privileged Access Only</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Notifications (Placeholder Icon) -->
                    <button class="w-12 h-12 rounded-2xl bg-muted/50 border border-border flex items-center justify-center text-foreground hover:text-accent transition-all relative">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-3 right-3 w-2 h-2 bg-accent rounded-full border-2 border-background animate-pulse"></span>
                    </button>

                    <div class="flex items-center bg-muted/50 border border-border rounded-2xl p-1 gap-1">
                        <button @click="darkMode = false" :class="!darkMode ? 'bg-white text-accent shadow-sm' : 'text-muted-foreground'" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                            <i class="fas fa-sun text-sm"></i>
                        </button>
                        <button @click="darkMode = true" :class="darkMode ? 'bg-background text-accent shadow-sm' : 'text-muted-foreground'" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                            <i class="fas fa-moon text-sm"></i>
                        </button>
                    </div>

                    <div class="h-8 w-[1px] bg-border/50 mx-2 hidden sm:block"></div>

                    <div class="flex items-center gap-4 group cursor-pointer lg:pr-4">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-black text-foreground leading-none mb-1"><?php echo clean($user['name'] ?? 'Member'); ?></p>
                            <span class="text-[8px] font-black uppercase tracking-[0.2em] px-2 py-0.5 bg-accent/10 text-accent rounded-full border border-accent/20">Member</span>
                        </div>
                        <div class="w-12 h-12 bg-accent/10 border border-accent/20 rounded-2xl flex items-center justify-center text-accent font-black text-xl shadow-lg transition-all duration-500 overflow-hidden relative">
                            <span><?php echo substr($user['name'] ?? 'M', 0, 1); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto custom-scrollbar p-6 md:p-12">
                <div class="max-w-[1400px] mx-auto reveal-content">
                    <?php echo $content; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
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
