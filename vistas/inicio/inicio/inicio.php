<?php
session_start();

// Comprobación de la sesión
if (!isset($_SESSION['usuario'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Por favor, debes iniciar sesión",
                icon: "warning",
                confirmButtonText: "Aceptar"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = "../../login/login.php";
                }
            });
        });
    </script>
    ';
    session_destroy();
    die();
}

// Mostrar mensaje de inicio de sesión exitoso
if (isset($_SESSION['login_exitoso'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "¡Bienvenido/a!",
                text: "Gracias por visitar UECFT Araure.",
                icon: "success",
                confirmButtonText: "Aceptar",
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
    ';
    unset($_SESSION['login_exitoso']);
}
?>
<head>
    <title>UECFT Araure - Inicio</title>
</head>
<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<!-- Contenido Principal -->
<main class="main-content d-flex align-items-center justify-content-center">
    <div class="text-center py-5">
        <div class="mb-4">
            <img src="../../../assets/images/fermin.png" alt="Logo UECFT Araure" class="img-logo">
        </div>
        <h1 class="display-5 fw-bold text-gray-800 mb-3">
            Bienvenido/a, <span class="text-danger"><?php echo htmlspecialchars($userNombre . ' ' . $userApellido); ?></span> 👋
        </h1>
        <p class="lead text-muted mb-4">
            Ya puedes comenzar a gestionar el sistema educativo.
        </p>
        <div class="btn-group">
            <a href="../../inscripciones/inscripcion/inscripcion.php" class="btn btn-lg btn-danger px-4 shadow-sm">
                <i class="fas fa-edit me-2"></i>Insc. Pendientes
            </a>
            <a href="../../estudiantes/estudiante/estudiante.php" class="btn btn-lg btn-outline-secondary px-4 shadow-sm">
                <i class="fas fa-users me-2"></i>Estudiantes
            </a>
        </div>
    </div>
</main>

<?php include '../../layouts/footer.php'; ?>