<?php
/**
 * includes/component/social-links.php
 * Reusable social links component
 */
function renderSocialLinks($class = 'gap-4') {
    $socials = [
        ['icon' => 'fa-brands fa-facebook-f', 'url' => '#', 'label' => 'Facebook'],
        ['icon' => 'fa-brands fa-x-twitter', 'url' => '#', 'label' => 'Twitter'],
        ['icon' => 'fa-brands fa-instagram', 'url' => '#', 'label' => 'Instagram'],
        ['icon' => 'fa-brands fa-linkedin-in', 'url' => '#', 'label' => 'LinkedIn']
    ];
    ?>
    <div class="flex items-center <?php echo $class; ?>">
        <?php foreach ($socials as $social): ?>
            <a href="<?php echo $social['url']; ?>" aria-label="<?php echo $social['label']; ?>" class="w-10 h-10 rounded-xl border border-border/50 flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-white hover:border-accent transition-all duration-300">
                <i class="<?php echo $social['icon']; ?> text-sm"></i>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}
