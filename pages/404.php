<?php
/**
 * pages/404.php
 * Error Page
 */

$pageTitle = 'Page Not Found';
include_once __DIR__ . '/../includes/layout/header.php';
?>

<section class="min-h-screen flex items-center justify-center pt-20">
    <div class="text-center px-4">
        <div class="text-9xl font-bold text-gray-200 mb-4 animate__animated animate__bounceIn">404</div>
        <h1 class="text-4xl font-bold text-gray-900 mb-6">Oops! Page Lost in Transit</h1>
        <p class="text-gray-600 text-lg mb-10 max-w-md mx-auto">
            The page you are looking for doesn't exist or has been moved to a different parking spot.
        </p>
        <a href="<?php echo url(); ?>" class="inline-flex items-center justify-center px-8 py-4 bg-accent text-white rounded-xl font-bold hover:bg-orange-700 transition">
            <i class="fas fa-home mr-2"></i>Back to Showroom
        </a>
    </div>
</section>

<?php include_once __DIR__ . '/../includes/layout/footer.php'; ?>
