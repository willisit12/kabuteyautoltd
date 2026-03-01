/**
 * Optimized Animation Controller
 * Handles smooth sidebar transitions and scroll animations
 * Cards and sections are visible by default - no hiding
 */

(function() {
    'use strict';

    // Smooth mobile menu toggle
    window.optimizedToggleMobileMenu = function() {
        const menu = document.getElementById('mobile-menu');
        if (!menu) return;

        const isOpen = menu.classList.contains('open');

        if (!isOpen) {
            // Opening
            menu.classList.add('open');
            document.body.style.overflow = 'hidden';

            // Animate menu items
            const items = menu.querySelectorAll('nav > *');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 100 + (index * 30));
            });
        } else {
            // Closing
            const items = menu.querySelectorAll('nav > *');
            items.forEach((item) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(10px)';
            });

            setTimeout(() => {
                menu.classList.remove('open');
                document.body.style.overflow = '';
            }, 200);
        }
    };

    // Debounced scroll handler
    let scrollTimeout;
    let lastScrollY = window.scrollY;

    function handleScroll() {
        const currentScrollY = window.scrollY;
        const nav = document.getElementById('main-nav');
        const progress = document.getElementById('scroll-progress');

        if (!nav) return;

        // Update progress bar
        if (progress) {
            const scrollPercent = (currentScrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            progress.style.width = scrollPercent + '%';
        }

        // Nav background on scroll
        if (currentScrollY > 50) {
            nav.classList.add('glass', 'shadow-lg', 'border-border/10');
            nav.classList.remove('border-transparent');
        } else {
            nav.classList.remove('glass', 'shadow-lg', 'border-border/10');
            nav.classList.add('border-transparent');
        }

        lastScrollY = currentScrollY;
    }

    // Throttled scroll listener
    window.addEventListener('scroll', () => {
        if (!scrollTimeout) {
            scrollTimeout = setTimeout(() => {
                handleScroll();
                scrollTimeout = null;
            }, 10);
        }
    }, { passive: true });

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', handleScroll);
    } else {
        handleScroll();
    }

})();
