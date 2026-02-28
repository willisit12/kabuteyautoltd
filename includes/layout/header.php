<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <meta name="description" content="<?php echo SITE_TAGLINE; ?>">
    <link rel="icon" href="<?php echo url('images/car.png'); ?>">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">

    <!-- Core Styles & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Animation & Interactivity Libraries -->
    <link rel="stylesheet" href="<?php echo url('assets/css/animate.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo url('assets/css/swiper-bundle.min.css'); ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    
    <script src="<?php echo url('assets/js/gsap.min.js'); ?>"></script>
    <script src="<?php echo url('assets/js/ScrollTrigger.min.js'); ?>"></script>
    <script src="<?php echo url('assets/js/swiper-bundle.min.js'); ?>"></script>
    <script src="<?php echo url('assets/js/lenis.min.js'); ?>"></script>
    <script src="<?php echo url('assets/js/motion.js?v=' . time()); ?>"></script>
    <script defer src="https://unpkg.com/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="<?php echo url('assets/js/alpine.min.js?v=' . time()); ?>"></script>

    <?php 
    // Load reusable components
    require_once __DIR__ . '/../component/logo.php';
    require_once __DIR__ . '/../component/theme-toggle.php';
    require_once __DIR__ . '/../component/social-links.php';
    require_once __DIR__ . '/../component/car-card.php';
    require_once __DIR__ . '/../component/preloader.php';
    ?>

    <script>
        // Professional Theme Detection - Immediate Head Execution to prevent FOLM
        (function() {
            const theme = localStorage.getItem('admin-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'true' || theme === '"true"' || (!theme && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            window.BASE_URL = '<?php echo SITE_URL; ?>';
            window.isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            window.csrfToken = '<?php echo generateCSRFToken(); ?>';
        })();
    </script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: 'rgb(var(--background-rgb) / <alpha-value>)',
                        foreground: 'rgb(var(--foreground-rgb) / <alpha-value>)',
                        card: 'rgb(var(--card-rgb) / <alpha-value>)',
                        'card-foreground': 'rgb(var(--card-foreground-rgb) / <alpha-value>)',
                        primary: {
                            DEFAULT: 'rgb(var(--primary-rgb) / <alpha-value>)',
                            foreground: 'rgb(var(--primary-foreground-rgb) / <alpha-value>)'
                        },
                        muted: {
                            DEFAULT: 'rgb(var(--muted-rgb) / <alpha-value>)',
                            foreground: 'rgb(var(--muted-foreground-rgb) / <alpha-value>)'
                        },
                        border: 'rgb(var(--border-rgb) / <alpha-value>)',
                        accent: {
                            DEFAULT: '#f97316',
                            foreground: '#ffffff'
                        },
                        'glass-bg': 'var(--glass-bg)',
                        'glass-border': 'var(--glass-border)'
                    },
                    fontFamily: {
                        'jakarta': ['"Plus Jakarta Sans"', 'sans-serif'],
                        'outfit': ['Outfit', 'sans-serif']
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        :root { 
            --accent-glow: 249 115 22;
            --background-rgb: 255 255 255;
            --foreground-rgb: 15 23 42;
            --card-rgb: 248 250 252;
            --card-foreground-rgb: 30 41 59;
            --primary-rgb: 15 23 42;
            --primary-foreground-rgb: 255 255 255;
            --muted-rgb: 241 245 249;
            --muted-foreground-rgb: 100 116 139;
            --border-rgb: 226 232 240;
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(15, 23, 42, 0.1);

            /* Standard variables for legacy use */
            --background: rgb(var(--background-rgb));
            --foreground: rgb(var(--foreground-rgb));
        }

        .dark {
            --background-rgb: 15 23 42;
            --foreground-rgb: 248 250 252;
            --card-rgb: 255 255 255 / 0.03;
            --card-foreground-rgb: 248 250 252;
            --primary-rgb: 249 113 22;
            --primary-foreground-rgb: 255 255 255;
            --muted-rgb: 255 255 255 / 0.05;
            --muted-foreground-rgb: 148 163 184;
            --border-rgb: 255 255 255 / 0.08;
            --glass-bg: rgba(15, 23, 42, 0.1);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        /* Prevent FOLM - Enforce dark background immediately if .dark class is present */
        .dark body, .dark .bg-background {
            background-color: var(--background) !important;
            color: var(--foreground) !important;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            overflow-x: hidden;
            background-color: var(--background);
            color: var(--foreground);
        }
        h1, h2, h3, .font-heading { font-family: 'Outfit', sans-serif; }
        
        /* Smooth Scroll Smoothing */
        html.lenis { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto !important; }
        .lenis.lenis-smooth [data-lenis-prevent] { overscroll-behavior: contain; }
        .lenis.lenis-stopped { overflow: hidden; }
        .lenis.lenis-scrolling iframe { pointer-events: none; }

        /* Custom Glassmorphism */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
        }

        .text-gradient {
            background: linear-gradient(to right, #f97316, #fb923c, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-premium {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        }
        .btn-premium::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        .btn-premium:hover::after { transform: translateX(100%); }

        /* Scroll Progress Bar */
        #scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(to right, #f97316, #fbbf24);
            z-index: 9999;
        }

        [x-cloak] { display: none !important; }

        /* Page Preloader Styles */
        #preloader {
            position: fixed;
            inset: 0;
            background: rgb(var(--background-rgb));
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.8s;
        }

        #preloader.loaded {
            opacity: 0;
            visibility: hidden;
        }

        .loader-content {
            position: relative;
            display: flex;
            flex-col: column;
            align-items: center;
            animation: loader-pulse 2s ease-in-out infinite;
        }

        .loader-ring {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            border-top-color: transparent;
            animation: loader-spin 1s linear infinite;
        }

        .loader-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 14px;
            letter-spacing: 0.2em;
            color: var(--accent);
            text-transform: uppercase;
        }

        @keyframes loader-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes loader-pulse {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        body.loading {
            overflow: hidden !important;
        }
    </style>
    <?php renderPreloaderCSS(); ?>
</head>
<body 
    x-data="{ 
        darkMode: $persist(document.documentElement.classList.contains('dark')).as('admin-theme'),
        mobileMenu: false
    }" 
    x-init="$watch('darkMode', val => {
        if (val) document.documentElement.classList.add('dark');
        else document.documentElement.classList.remove('dark');
    })"
    :class="{ 'dark': darkMode }"
    class="bg-background text-foreground selection:bg-accent selection:text-white transition-colors duration-500 loading"
>
    <!-- Cinematic Preloader -->
    <?php renderPreloaderHTML(url('images/car.png')); ?>

    <div id="scroll-progress"></div>

    <!-- Navigation -->
    <nav class="fixed w-full top-0 z-[100] transition-all duration-500 border-b border-transparent" id="main-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 md:h-24 transition-all duration-300">
                <div class="flex items-center">
                    <?php renderLogo('h-8 md:h-12'); ?>
                </div>
               
                <div class="hidden md:flex items-center space-x-10">
                    <a href="<?php echo url(); ?>" class="text-foreground/70 hover:text-accent transition-colors duration-300 font-semibold tracking-wide"><?php echo __('nav_home'); ?></a>
                    <a href="<?php echo url('cars'); ?>" class="text-foreground/70 hover:text-accent transition-colors duration-300 font-semibold tracking-wide"><?php echo __('nav_inventory'); ?></a>
                    <a href="<?php echo url('about'); ?>" class="text-foreground/70 hover:text-accent transition-colors duration-300 font-semibold tracking-wide"><?php echo __('nav_about'); ?></a>
                    <a href="<?php echo url('contact'); ?>" class="text-foreground/70 hover:text-accent transition-colors duration-300 font-semibold tracking-wide"><?php echo __('nav_contact'); ?></a>
                    
                    <div class="h-6 w-[1px] bg-border/20 ml-4"></div>

                    <!-- Localization & Currency Switchers -->
                    <div class="flex items-center gap-3">
                        <!-- Language Switcher -->
                        <div class="relative group" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border/50 hover:border-accent/50 transition-all text-sm font-bold text-foreground/80">
                                <span class="uppercase"><?php echo I18n::getLocale(); ?></span>
                                <i class="fas fa-chevron-down text-[10px] opacity-50"></i>
                            </button>
                            <div x-show="open" x-cloak class="absolute top-full right-0 mt-2 w-32 bg-background border border-border rounded-2xl shadow-2xl p-2 z-[200]">
                                <a href="?lang=en" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-muted transition-all text-sm font-bold <?php echo I18n::getLocale() === 'en' ? 'text-accent' : 'text-foreground'; ?>">
                                    <span>EN</span> <?php echo __('lang_en'); ?>
                                </a>
                                <a href="?lang=es" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-muted transition-all text-sm font-bold <?php echo I18n::getLocale() === 'es' ? 'text-accent' : 'text-foreground'; ?>">
                                    <span>ES</span> <?php echo __('lang_es'); ?>
                                </a>
                                <a href="?lang=zh" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-muted transition-all text-sm font-bold <?php echo I18n::getLocale() === 'zh' ? 'text-accent' : 'text-foreground'; ?>">
                                    <span>ZH</span> <?php echo __('lang_zh'); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Currency Switcher -->
                        <div class="relative group" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border/50 hover:border-accent/50 transition-all text-sm font-bold text-foreground/80">
                                <span><?php echo I18n::getCurrency(); ?></span>
                                <i class="fas fa-chevron-down text-[10px] opacity-50"></i>
                            </button>
                            <div x-show="open" x-cloak class="absolute top-full right-0 mt-2 w-32 bg-background border border-border rounded-2xl shadow-2xl p-2 z-[200]">
                                <?php foreach (['USD', 'EUR', 'GBP', 'AED', 'CNY', 'GHS'] as $curr): ?>
                                    <a href="?currency=<?php echo $curr; ?>" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-muted transition-all text-sm font-bold <?php echo I18n::getCurrency() === $curr ? 'text-accent' : 'text-foreground'; ?>">
                                        <?php echo $curr; ?>
                                        <?php if (I18n::getCurrency() === $curr): ?>
                                            <i class="fas fa-check text-[10px]"></i>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="h-6 w-[1px] bg-border/20"></div>

                    <?php if (isLoggedIn()): ?>
                        <?php 
                        $user = getUserInfo(); 
                        $unreadCount = getUnreadNotificationsCount($user['id']);
                        $recentNotifications = getRecentNotifications($user['id'], 5);
                        ?>
                        <!-- Persistent Notifications -->
                        <div x-data="{ nOpen: false }" class="relative">
                            <button @click="nOpen = !nOpen" @click.away="nOpen = false" class="relative group p-2 text-foreground/60 hover:text-accent transition-all duration-300">
                                <i class="fas fa-bell text-lg"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="absolute top-1 right-1 w-4 h-4 bg-accent text-white rounded-full flex items-center justify-center text-[8px] font-black border-2 border-background animate-bounce">
                                        <?php echo $unreadCount; ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Notification Dropdown -->
                            <div 
                                x-show="nOpen" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                class="absolute right-0 mt-6 w-80 glass rounded-[2.5rem] border border-border/50 shadow-2xl z-50 overflow-hidden"
                                style="display: none;"
                            >
                                <div class="p-6 border-b border-border/30 bg-muted/30">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-foreground">Intelligence Alerts</h3>
                                </div>
                                <div class="max-h-96 overflow-y-auto divide-y divide-border/20">
                                    <?php if (empty($recentNotifications)): ?>
                                        <div class="p-10 text-center">
                                            <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest italic">No pending alerts</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recentNotifications as $notif): ?>
                                            <a href="<?php echo url($notif['link'] ?: 'dashboard'); ?>" class="block p-6 hover:bg-accent/[0.03] transition-all <?php echo $notif['is_read'] ? 'opacity-60' : ''; ?>">
                                                <div class="flex items-start gap-4">
                                                    <div class="w-2 h-2 rounded-full mt-2 <?php echo $notif['is_read'] ? 'bg-muted' : 'bg-accent animate-pulse'; ?>"></div>
                                                    <div class="flex-1">
                                                        <h4 class="text-[11px] font-black text-foreground uppercase tracking-tight mb-1"><?php echo clean($notif['title']); ?></h4>
                                                        <p class="text-[10px] font-medium text-muted-foreground leading-relaxed line-clamp-2 italic">"<?php echo clean($notif['message']); ?>"</p>
                                                        <span class="text-[7px] font-black uppercase text-accent/50 mt-2 block"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4 bg-muted/20 border-t border-border/30 text-center">
                                    <a href="<?php echo url('dashboard'); ?>" class="text-[8px] font-black uppercase tracking-widest text-muted-foreground hover:text-accent transition-colors">Intelligence Portfolio</a>
                                </div>
                            </div>
                        </div>

                        <a href="<?php echo url('dashboard'); ?>" class="glass px-6 py-2.5 rounded-full text-foreground hover:bg-accent hover:text-white transition-all font-black uppercase tracking-widest text-[9px] flex items-center gap-2 group ml-2 shadow-sm">
                            <i class="fas fa-id-badge text-accent group-hover:text-white"></i> 
                            Portal
                        </a>
                    <?php else: ?>
                        <div class="flex items-center gap-4 ml-2">
                            <a href="<?php echo url('login'); ?>" class="text-[10px] font-black uppercase tracking-widest text-foreground/60 hover:text-accent transition-colors">Login</a>
                            <a href="<?php echo url('register'); ?>" class="px-7 py-3 rounded-full bg-accent text-white text-[10px] font-black uppercase tracking-[0.2em] shadow-[0_10px_30px_rgba(249,115,22,0.3)] hover:scale-105 active:scale-95 transition-all">Join Circle</a>
                        </div>
                    <?php endif; ?>

                    <!-- Theme Toggle Component -->
                    <?php renderThemeToggle(); ?>
                </div>

                <!-- Mobile Trigger -->
                <div class="md:hidden flex items-center gap-4 relative z-[101]">
                    <button class="text-foreground p-2 hover:text-accent transition-colors" @click="toggleMobileMenu(); mobileMenu = !mobileMenu">
                        <i class="fas fa-bars-staggered text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="mobileMenu" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="toggleMobileMenu(); mobileMenu = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[200]"
         x-cloak></div>

    <!-- Premium Mobile Sidebar -->
    <aside id="mobile-menu" 
           class="fixed top-0 left-[-20rem] h-full w-80 bg-[#0f172a] text-white flex flex-col z-[300] transition-transform duration-300 ease-in-out border-r border-white/5 shadow-2xl overflow-hidden"
           x-cloak>
        
        <div class="p-8 pb-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-car text-xl"></i>
                </div>
                <div>
                    <h1 class="font-black tracking-tighter uppercase text-lg leading-none"><?php echo SITE_NAME; ?></h1>
                    <p class="text-[8px] font-black uppercase tracking-[0.3em] text-accent mt-1">Toronto Elite</p>
                </div>
            </div>
            <button @click="toggleMobileMenu(); mobileMenu = false" class="text-white/40 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-6 py-8 space-y-2 overflow-y-auto custom-scrollbar">
            <p class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-4 ml-2">Navigation</p>
            
            <a href="<?php echo url(); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-white/5 group">
                <i class="fas fa-home w-5 text-accent group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm tracking-tight"><?php echo __('nav_home'); ?></span>
            </a>
            <a href="<?php echo url('cars'); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-white/5 group">
                <i class="fas fa-car w-5 text-accent group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm tracking-tight"><?php echo __('nav_inventory'); ?></span>
            </a>
            <a href="<?php echo url('about'); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-white/5 group">
                <i class="fas fa-info-circle w-5 text-accent group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm tracking-tight"><?php echo __('nav_about'); ?></span>
            </a>
            <a href="<?php echo url('contact'); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all hover:bg-white/5 group">
                <i class="fas fa-envelope w-5 text-accent group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm tracking-tight"><?php echo __('nav_contact'); ?></span>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo url('dashboard'); ?>" class="flex items-center gap-4 px-5 py-4 rounded-2xl bg-white/5 border border-white/10 group">
                    <i class="fas fa-id-badge w-5 text-accent group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-sm tracking-tight">Member Portal</span>
                </a>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-3 px-2 pt-4">
                    <a href="<?php echo url('login'); ?>" class="py-4 rounded-2xl border border-white/10 bg-white/5 text-center font-black uppercase tracking-widest text-[10px]">Login</a>
                    <a href="<?php echo url('register'); ?>" class="py-4 rounded-2xl bg-accent text-white text-center font-black uppercase tracking-widest text-[10px]">Join Circle</a>
                </div>
            <?php endif; ?>

            <p class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-4 ml-2 pt-6">Preferences</p>
            
            <!-- Mobile Localization & Currency -->
            <div class="space-y-3 px-2">
                <!-- Theme Mode -->
                <?php renderThemeToggle(true); ?>

                <!-- Language Dropdown -->
                <div x-data="{ open: false }" class="w-full">
                    <button @click="open = !open" class="flex items-center justify-between w-full p-4 rounded-2xl border border-white/5 bg-white/5 text-white font-bold transition-all hover:border-accent/30">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-globe text-accent text-sm"></i>
                            <span class="uppercase text-sm"><?php echo I18n::getLocale(); ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-[10px] opacity-40 transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="mt-2 flex flex-col gap-1 p-2 bg-white/5 rounded-2xl border border-white/5 overflow-hidden">
                        <a href="?lang=en" class="flex items-center justify-between p-3 rounded-xl transition-all <?php echo I18n::getLocale() === 'en' ? 'bg-accent text-white shadow-lg' : 'hover:bg-white/5 text-white/60'; ?>">
                            <span class="text-xs font-black uppercase tracking-widest">English</span>
                        </a>
                        <a href="?lang=es" class="flex items-center justify-between p-3 rounded-xl transition-all <?php echo I18n::getLocale() === 'es' ? 'bg-accent text-white shadow-lg' : 'hover:bg-white/5 text-white/60'; ?>">
                            <span class="text-xs font-black uppercase tracking-widest">Español</span>
                        </a>
                        <a href="?lang=zh" class="flex items-center justify-between p-3 rounded-xl transition-all <?php echo I18n::getLocale() === 'zh' ? 'bg-accent text-white shadow-lg' : 'hover:bg-white/5 text-white/60'; ?>">
                            <span class="text-xs font-black uppercase tracking-widest">中文 (Chinese)</span>
                        </a>
                    </div>
                </div>

                <!-- Currency Dropdown -->
                <div x-data="{ open: false }" class="w-full">
                    <button @click="open = !open" class="flex items-center justify-between w-full p-4 rounded-2xl border border-white/5 bg-white/5 text-white font-bold transition-all hover:border-accent/30">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-coins text-accent text-sm"></i>
                            <span class="uppercase text-sm"><?php echo I18n::getCurrency(); ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-[10px] opacity-40 transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak class="mt-2 grid grid-cols-2 gap-2 p-2 bg-white/5 rounded-2xl border border-white/5 overflow-hidden">
                        <?php foreach (['USD', 'EUR', 'GBP', 'AED', 'CNY', 'GHS'] as $curr): ?>
                            <a href="?currency=<?php echo $curr; ?>" class="flex items-center justify-between p-3 rounded-xl transition-all <?php echo I18n::getCurrency() === $curr ? 'bg-accent text-white shadow-lg' : 'hover:bg-white/5 text-white/60'; ?>">
                                <span class="text-xs font-black"><?php echo $curr; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="p-8 border-t border-white/5 flex flex-col gap-6">
            <?php renderSocialLinks('justify-center gap-6'); ?>
            
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo url('admin/dashboard'); ?>" class="flex items-center justify-center gap-3 bg-white/5 hover:bg-accent text-white p-5 rounded-[2rem] font-black uppercase tracking-tighter transition-all shadow-xl group">
                    <i class="fas fa-grid-2 text-accent group-hover:text-white"></i> Dashboard access
                </a>
            <?php else: ?>
                <a href="<?php echo url('login'); ?>" class="flex items-center justify-center gap-3 bg-accent text-white p-5 rounded-[2rem] font-black uppercase tracking-tighter transition-all shadow-2xl hover:scale-[1.02] active:scale-[0.98]">
                    <i class="fas fa-user-circle"></i> Member Entrance
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <script>
        const { animate, stagger } = Motion;
        let isMenuOpen = false;
        let lenis;

        // Initialize Lenis with Safety
        if (typeof Lenis !== 'undefined') {
            lenis = new Lenis();
            function raf(time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);
        }

        window.toggleMobileMenu = async function() {
            const menu = document.getElementById('mobile-menu');
            const links = document.querySelectorAll('#mobile-menu nav > *');
            
            if (!isMenuOpen) {
                isMenuOpen = true;
                if (lenis) lenis.stop();
                
                // Show menu from left
                await animate(menu, { x: "100%" }, { 
                    duration: 0.4, 
                    easing: [0.16, 1, 0.3, 1] 
                }).finished;

                animate(links, { opacity: [0, 1], y: [20, 0] }, { 
                    delay: stagger(0.04),
                    duration: 0.3
                });

            } else {
                isMenuOpen = false;
                
                animate(links, { opacity: 0, y: 10 }, { 
                    duration: 0.3 
                });

                // Hide menu back to left
                await animate(menu, { x: 0 }, { 
                    duration: 0.3,
                    easing: "ease-in"
                }).finished;
                
                if (lenis) lenis.start();
            }
        }

        // Header Scroll Effect
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('main-nav');
            const progress = document.getElementById('scroll-progress');
            const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            
            if (progress) progress.style.width = scrollPercent + '%';

            if (window.scrollY > 50) {
                nav.classList.add('glass', 'h-20', 'border-border/10', 'shadow-lg');
                nav.classList.remove('h-24', 'border-transparent');
            } else {
                nav.classList.remove('glass', 'h-20', 'border-border/10', 'shadow-lg');
                nav.classList.add('h-24', 'border-transparent');
            }
        });

        // Initialize GSAP with Safety
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
            gsap.registerPlugin(ScrollTrigger);
        }

        // Preloader Logic
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                document.body.classList.remove('loading');
                document.body.classList.remove('preloader-active');
                preloader.classList.add('loaded');
                
                // Re-enable Lenis if present
                if (typeof lenis !== 'undefined' && lenis) lenis.start();

                // Reveal animations if Motion is present
                if (typeof Motion !== 'undefined') {
                    Motion.animate(".global-reveal", { opacity: [0, 1], y: [20, 0] }, { 
                        delay: Motion.stagger(0.1),
                        duration: 0.8,
                        easing: [0.16, 1, 0.3, 1]
                    });
                }
            }
        });
        // Fallback: force hide after 3s
        setTimeout(() => { 
            const p = document.getElementById('preloader');
            if (p) { p.classList.add('loaded'); document.body.classList.remove('loading','preloader-active'); }
        }, 3000);
    </script>

    <!-- Authentication Interceptor Modal -->
    <div x-data="{ open: false }" 
         x-show="open" 
         @open-login-modal.window="open = true"
         x-cloak
         class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="open = false" 
             class="bg-white dark:bg-card w-full max-w-sm rounded-[2.5rem] p-10 shadow-2xl relative overflow-hidden text-center border border-border/10">
            
            <button @click="open = false" class="absolute top-6 right-6 text-foreground/40 hover:text-foreground transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="mb-8">
                <div class="w-16 h-16 bg-accent/10 rounded-2xl flex items-center justify-center mx-auto mb-6 text-accent">
                    <i class="fas fa-shield-heart text-3xl"></i>
                </div>
                <h3 class="text-2xl font-black text-foreground tracking-tight uppercase mb-2">Attention</h3>
                <p class="text-sm font-bold text-muted-foreground opacity-70">Please log in to build your curated collection.</p>
            </div>

            <a href="<?php echo url('login'); ?>" 
               class="inline-block w-full py-5 rounded-[2rem] bg-[#00c58d] text-white font-black uppercase tracking-widest text-[10px] shadow-[0_10px_30px_rgba(0,197,141,0.2)] hover:scale-[1.02] active:scale-[0.98] transition-all">
                Member Entrance
            </a>
        </div>
    </div>

    <!-- Global Intelligence Notifications -->
    <div x-data="{ notifications: [] }" 
         @notify.window="let id = Date.now(); notifications.push({ id, ...$event.detail }); setTimeout(() => notifications = notifications.filter(n => n.id !== id), 4000)"
         class="fixed bottom-8 right-8 z-[1001] flex flex-col gap-4 pointer-events-none">
        <template x-for="n in notifications" :key="n.id">
            <div x-transition
                 class="glass px-8 py-5 rounded-3xl shadow-2xl flex items-center gap-4 border-l-4 pointer-events-auto min-w-[300px]"
                 :class="n.type === 'success' ? 'border-green-500' : 'border-red-500'">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0" :class="n.type === 'success' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'">
                    <i class="fas" :class="n.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black uppercase tracking-widest opacity-40" x-text="n.type === 'success' ? 'Intelligence Update' : 'System Alert'"></span>
                    <span class="font-bold text-sm text-foreground" x-text="n.message"></span>
                </div>
            </div>
        </template>
    </div>
</body>
