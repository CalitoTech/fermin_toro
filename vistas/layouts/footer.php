<style>
    .designer-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background-color: rgba(255, 255, 255, 0.08);
        padding: 6px 16px;
        border-radius: 50px;
        color: #e0e0e0 !important;
        text-decoration: none !important;
        font-weight: 500;
        font-size: 0.85rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        margin-left: 8px;
    }

    .designer-btn:hover {
        background-color: #0077b5; /* LinkedIn Blue */
        color: #fff !important;
        border-color: #0077b5;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 119, 181, 0.4);
    }

    .designer-btn i {
        font-size: 1.1em;
    }
</style>

<footer class="bg-dark text-white text-center py-4 small" style="margin-top: auto; width: 100%; position: relative; z-index: 10;">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-md-12">
                <p class="mb-2">
                    &copy; <?php echo date('Y'); ?> <strong>UECFT Araure</strong>. Todos los derechos reservados.
                </p>
                <div class="d-flex justify-content-center align-items-center">
                    <span style="opacity: 0.7; font-size: 0.85rem;">Diseñado por:</span>
                    <a href="https://www.linkedin.com/in/carlos-navas04/" target="_blank" class="designer-btn">
                        <i class="fab fa-linkedin"></i>
                        <span>Carlos Navas</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../../assets/js/main.js"></script>
</body>
</html>