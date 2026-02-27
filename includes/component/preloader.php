<?php
/**
 * includes/component/preloader.php
 * Global Cinematic Page Preloader Component
 * Include the CSS in <head> with renderPreloaderCSS() 
 * Include the HTML right after <body> with renderPreloaderHTML()
 * Include the JS before </body> with renderPreloaderJS()
 */

function renderPreloaderCSS() { ?>
<style>
    /* ─── Page Preloader ─── */
    #preloader {
        position: fixed;
        inset: 0;
        background: rgb(var(--background-rgb, 255 255 255));
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.6s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.6s;
    }
    #preloader.loaded {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .preloader__spinner {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .preloader__ring {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        position: relative;
    }
    .preloader__ring::before,
    .preloader__ring::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid transparent;
    }
    .preloader__ring::before {
        border-top-color: var(--accent, #f97316);
        border-right-color: var(--accent, #f97316);
        animation: preloader-spin 0.8s linear infinite;
    }
    .preloader__ring::after {
        border-bottom-color: rgba(249, 115, 22, 0.3);
        border-left-color: rgba(249, 115, 22, 0.3);
        animation: preloader-spin 1.2s linear infinite reverse;
    }

    .preloader__logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 28px;
        height: 28px;
        object-fit: contain;
    }
    .preloader__logo-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-family: 'Outfit', sans-serif;
        font-weight: 900;
        font-size: 11px;
        letter-spacing: 0.15em;
        color: var(--accent, #f97316);
        text-transform: uppercase;
    }

    .preloader__bar {
        width: 120px;
        height: 2px;
        background: rgba(var(--foreground-rgb, 15 23 42), 0.08);
        border-radius: 999px;
        overflow: hidden;
    }
    .preloader__bar-fill {
        width: 40%;
        height: 100%;
        background: var(--accent, #f97316);
        border-radius: 999px;
        animation: preloader-progress 1s ease-in-out infinite;
    }

    @keyframes preloader-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    @keyframes preloader-progress {
        0% { margin-left: 0; width: 30%; }
        50% { width: 50%; }
        100% { margin-left: 100%; width: 30%; }
    }

    body.preloader-active {
        overflow: hidden !important;
    }
</style>
<?php }

function renderPreloaderHTML($logoUrl = null) { ?>
<div id="preloader">
    <div class="preloader__spinner">
        <div class="preloader__ring">
            <?php if ($logoUrl): ?>
                <img src="<?php echo $logoUrl; ?>" alt="" class="preloader__logo">
            <?php else: ?>
                <span class="preloader__logo-text">KA</span>
            <?php endif; ?>
        </div>
        <div class="preloader__bar">
            <div class="preloader__bar-fill"></div>
        </div>
    </div>
</div>
<?php }

function renderPreloaderJS() { ?>
<script>
(function() {
    var preloader = document.getElementById('preloader');
    if (!preloader) return;
    
    document.body.classList.add('preloader-active');
    
    function hidePreloader() {
        preloader.classList.add('loaded');
        document.body.classList.remove('preloader-active');
    }
    
    // Hide on load or after 3s max
    window.addEventListener('load', function() {
        setTimeout(hidePreloader, 200);
    });
    setTimeout(hidePreloader, 3000);
})();
</script>
<?php }
?>
