<style>
/* Sticky Social Menu Styles */
.sticky-social-menu {
    position: fixed;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end; /* Prevent items from stretching to container width */
}

/* Ocultar el menú social cuando hay un modal abierto */
body.modal-open .sticky-social-menu {
    z-index: 1040; /* Por debajo del modal backdrop (1050) */
}

.sticky-social-menu a {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 50px; /* Collapsed width */
    height: 50px;
    background-color: #333;
    color: white;
    text-decoration: none;
    border-radius: 5px 0 0 5px;
    transition: width 0.3s ease, background-color 0.3s;
    overflow: hidden;
    white-space: nowrap;
    padding-left: 15px; /* Center icon */
    box-shadow: -2px 2px 5px rgba(0,0,0,0.3);
    cursor: pointer;
}

.sticky-social-menu a:hover, .sticky-social-menu a.active {
    width: 200px; /* Expanded width */
}

.sticky-social-menu a i {
    font-size: 20px;
    min-width: 20px;
    margin-right: 15px;
}

.sticky-social-menu a span {
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sticky-social-menu a:hover span, .sticky-social-menu a.active span {
    opacity: 1;
}

/* Specific Colors */
.sticky-social-menu .instagram { background-color: #E1306C; }
.sticky-social-menu .youtube { background-color: #FF0000; }
.sticky-social-menu .email { background-color: #D44638; }
.sticky-social-menu .whatsapp { background-color: #25D366; }
.sticky-social-menu .maps { background-color: #4285F4; }
.sticky-social-menu .nuestravoz { background-color: #FF5722; }

/* Highlight animation */
@keyframes highlightMenu {
    0% { transform: translateY(-50%) scale(1); }
    50% { transform: translateY(-50%) scale(1.1); }
    100% { transform: translateY(-50%) scale(1); }
}

.sticky-social-menu.highlight {
    animation: highlightMenu 0.5s ease-in-out 3;
}
</style>

<div class="sticky-social-menu" id="stickySocialMenu">
    <a href="https://www.instagram.com/uecftaraure/" target="_blank" class="instagram" title="Instagram">
        <i class="fab fa-instagram"></i>
        <span>Instagram</span>
    </a>
    <a href="https://www.youtube.com/@NuestraVozRadioyTv" target="_blank" class="youtube" title="YouTube">
        <i class="fab fa-youtube"></i>
        <span>YouTube</span>
    </a>
    <a href="mailto:fermin.toro.araure@gmail.com" class="email" title="Correo">
        <i class="fas fa-envelope"></i>
        <span>Correo</span>
    </a>
    <a href="https://api.whatsapp.com/send?phone=584145641168" target="_blank" class="whatsapp" title="WhatsApp">
        <i class="fab fa-whatsapp"></i>
        <span>WhatsApp</span>
    </a>
    <a href="https://maps.app.goo.gl/XRBXfB6ygZDZaS3t7" target="_blank" class="maps" title="Ubicación">
        <i class="fas fa-map-marker-alt"></i>
        <span>Ubicación</span>
    </a>
    <a href="nuestravoz.php" target="_blank" class="nuestravoz" title="Nuestra Voz">
        <i class="fas fa-microphone"></i>
        <span>Nuestra Voz</span>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if URL has hash #redes
    if (window.location.hash === '#redes') {
        highlightSocialMenu();
    }

    // Listen for clicks on links to #redes
    document.querySelectorAll('a[href="#redes"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            highlightSocialMenu();
        });
    });

    function highlightSocialMenu() {
        const menu = document.getElementById('stickySocialMenu');
        menu.classList.add('highlight');
        
        // Expand all items briefly
        const items = menu.querySelectorAll('a');
        items.forEach(item => item.classList.add('active'));

        setTimeout(() => {
            menu.classList.remove('highlight');
            items.forEach(item => item.classList.remove('active'));
        }, 2000); // Keep expanded for 2 seconds
    }
});
</script>
