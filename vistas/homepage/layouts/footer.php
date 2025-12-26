<!-- Footer Design -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap');

    .site-footer {
        background: linear-gradient(135deg, #8a0000 0%, #c90000 100%);
        color: #fff;
        padding: 25px 0;
        font-family: 'Outfit', sans-serif;
        box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        margin-top: auto; /* Ensure it pushes to bottom if flex container used */
    }
    
    /* Decorative top border */
    .site-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 5%;
        max-width: 1400px;
        margin: 0 auto;
    }

    .copyright-text {
        font-size: 0.95rem;
        font-weight: 300;
        letter-spacing: 0.5px;
        opacity: 0.9;
    }

    .designer-section {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .designer-label {
        font-size: 0.9rem;
        font-weight: 300;
        opacity: 0.8;
        letter-spacing: 0.5px;
    }

    .designer-link {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 20px;
        border-radius: 50px;
        color: #fff;
        text-decoration: none !important;
        font-weight: 600;
        font-size: 0.95rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(5px);
    }

    .designer-link:hover {
        background: #fff;
        color: #c90000;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .designer-link i {
        font-size: 1.2rem;
        transition: transform 0.3s ease;
    }

    .designer-link:hover i {
        transform: scale(1.1) rotate(5deg);
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            gap: 20px;
            text-align: center;
            padding: 0 20px;
        }
        
        .designer-section {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-content">
        <div class="copyright-text">
            &copy; 2025 <strong>UECFT Araure</strong>. Todos los derechos reservados.
        </div>
        
        <div class="designer-section">
            <span class="designer-label">Diseñado por:</span>
            <a href="https://www.linkedin.com/in/carlos-navas04/" target="_blank" class="designer-link">
                <i class="fab fa-linkedin"></i>
                <span>Carlos Navas</span>
            </a>
        </div>
    </div>
</footer>

<!-- jQuery y Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<!-- Otros plugins -->
<script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.js"></script>

<!-- Tu script personalizado -->
<script src="js/custom.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Tus scripts personalizados -->
<script src="../../assets/js/buscador_generico.js"></script>
<script src="../../assets/js/validaciones_solicitud.js?v=9"></script>
<script src="../../assets/js/solicitud_cupo.js?v=19"></script>
<script src="../../assets/js/validacion.js?v=5"></script>

</body>
</html>
