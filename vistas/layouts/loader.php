<?php
// Determine the base URL for assets based on the script execution directory
// Helper to find the relative path to the root 'fermin_toro' folder
function getRelativeRootPath() {
    // Count how many directories deep we are from the root 'vistas' or 'controladores'
    // Simple heuristic: we look for the 'assets' folder.
    $path = '';
    for ($i = 0; $i < 5; $i++) {
        if (file_exists($path . 'assets/images/fermin.png')) {
            return $path;
        }
        $path .= '../';
    }
    return '../../'; // Fallback
}

$relativePath = getRelativeRootPath();
$logoPath = $relativePath . 'assets/images/fermin.png';

// Read CSS content to inline it (prevents FOUC)
$cssPath = __DIR__ . '/../homepage/css/loader.css';
$cssContent = file_exists($cssPath) ? file_get_contents($cssPath) : '';
?>

<!-- Loader Critical CSS -->
<style>
    <?php echo $cssContent; ?>
    /* Ensure styles that might depend on paths are corrected found? 
       loader.css generally uses pure CSS, check for url() usage. 
       If loader.css has no url(), we are good. */
       
    /* Override positioning just in case */
    .loader_bg {
        top: 0;
        left: 0;
    }
    .logo-pulse {
        /* User requested adjustment */
        left: 3px; 
        top: 60px;
    }
</style>

<!-- Loader HTML -->
<div class="loader_bg">
    <div class="loader-container">
        <div class="spinner-ring"></div>
        <div class="logo-pulse">
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="UECFT Araure">
        </div>
        <div class="loader-text">Cargando...</div>
    </div>
</div>

<!-- Loader Logic -->
<!-- Enqueueing script here or relying on global custom.js -->
<!-- Use inline script for failsafe removal, can work alongside custom.js -->
<script>
    window.addEventListener('load', function() {
        var loader = document.querySelector('.loader_bg');
        if (loader) {
            // Check if jQuery/custom.js is already handling it to avoid conflict?
            // Actually, custom.js uses fadeToggle. We can just force remove it after a delay if it's still there.
            setTimeout(function() {
                if(loader.style.display !== 'none') {
                    loader.style.transition = 'opacity 0.5s ease';
                    loader.style.opacity = '0';
                    setTimeout(function() { loader.remove(); }, 500);
                }
            }, 1000); // 1-second fallback backup
        }
    });
</script>
