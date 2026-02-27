<?php
/**
 * includes/component/logo.php
 * Reusable logo component
 */
function renderLogo($class = 'h-8 md:h-12', $link = null) {
    if ($link === null) $link = url();
    ?>
    <a href="<?php echo $link; ?>" class="group inline-flex items-center <?php echo $class; ?>">
        <img src="<?php echo url('images/logo-main.png'); ?>" alt="<?php echo SITE_NAME; ?>" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105 dark:brightness-110 brightness-90">
    </a>
    <?php
}
