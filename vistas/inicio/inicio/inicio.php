<?php
session_start();

// Comprobación de sesión (aseguramos idPersona y usuario)
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Por favor, debes iniciar sesión",
                icon: "warning",
                confirmButtonText: "Aceptar"
            }).then(() => window.location = "../../login/login.php");
        });
    </script>
    ';
    // destruimos sesión por seguridad
    session_unset();
    session_destroy();
    exit;
}

// Mensaje de bienvenida (solo si fue seteado durante el login)
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

// --- Uso CONSISTENTE de claves de sesión ---
// Según tu menu.php: 'idPersona' y 'idPerfil' (minúsculas)
$userNombre  = $_SESSION['nombre']   ?? '';
$userApellido= $_SESSION['apellido'] ?? '';
$perfilId    = $_SESSION['idPerfil'] ?? 0;   // <-- IMPORTANT: 'idPerfil' en minúsculas

// Si por alguna razón aún no tienes el idPerfil en sesión, puedes intentar
// recuperarlo desde la BD usando idPersona. (Opcional — solo si lo necesitas)
/*
if (empty($perfilId) && isset($_SESSION['idPersona'])) {
    // aquí podrías incluir la lógica para consultar la BD y setear $_SESSION['idPerfil']
}
*/

?>
<head>
    <meta charset="utf-8">
    <title>UECFT Araure - Inicio</title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* === ESTILO EXCLUSIVO REPRESENTANTES === */
        .rep-wrapper {
            min-height: calc(100vh - 56px - 50px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #fff7f7, #fff0f0);
            text-align: center;
            padding: 2rem;
        }

        .img-logo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 6px 20px rgba(201,0,0,0.12);
        }

        .rep-card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.25rem;
            margin-top: 1.75rem;
            max-width: 1100px;
        }

        .rep-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            padding: 1.6rem;
            width: 280px;
            transition: transform 0.28s ease, box-shadow 0.28s ease;
            text-decoration: none;
            color: #212529;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .rep-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 40px rgba(201,0,0,0.12);
        }

        .rep-card .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: rgba(201,0,0,0.06);
            color: #c90000;
            font-size: 24px;
        }

        .rep-card h3 {
            font-size: 18px;
            margin: 0;
        }

        .rep-card p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        .rep-welcome {
            font-size: 1.85rem;
            font-weight: 700;
            color: #c90000;
            margin-top: 10px;
        }

        .rep-subtitle {
            color: #606f7b;
            margin-top: 0.4rem;
            max-width: 760px;
        }

        @media (max-width: 768px) {
            .rep-card { width: 92%; }
            .rep-welcome { font-size: 1.45rem; }
        }
    </style>
</head>

<?php include '../../layouts/header.php'; ?>
<?php include '../../layouts/menu.php'; ?>

<?php if (in_array((int)$perfilId, [3,4,5], true)): ?>
    <!-- === INTERFAZ REPRESENTANTE === -->
    <main class="rep-wrapper">
        <img src="../../../assets/images/fermin.png" alt="Logo UECFT Araure" class="img-logo">
        <h1 class="rep-welcome">¡Hola, <?php echo htmlspecialchars($userNombre ?: 'Representante'); ?>! 👋</h1>
        <p class="rep-subtitle">Bienvenido/a al portal del representante. Gestiona inscripciones, consulta información de tus representados y recibe comunicados oficiales.</p>

        <div class="rep-card-container">
            <!-- <a href="../../inscripciones/representante/inscripciones.php" class="rep-card" title="Mis Inscripciones">
                <div class="icon"><i class="fas fa-file-signature"></i></div>
                <h3>Mis Inscripciones</h3>
                <p>Revisa solicitudes y estado de inscripciones.</p>
            </a> -->

            <a href="../../representantes/representados/ver_representado.php" class="rep-card" title="Mis Representados">
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Mis Representados</h3>
                <p>Accede al perfil académico y contacta con el colegio.</p>
            </a>

            <a href="#" class="rep-card" title="Comunicados">
                <div class="icon"><i class="fas fa-bullhorn"></i></div>
                <h3>Comunicados</h3>
                <p>Últimas noticias y avisos del instituto.</p>
            </a>
        </div>
    </main>
<?php else: ?>
    <!-- === INTERFAZ ADMINISTRATIVA ORIGINAL === -->
    <main class="main-content d-flex align-items-center justify-content-center">
        <div class="text-center py-5">
            <div class="mb-4"><img src="../../../assets/images/fermin.png" alt="Logo UECFT Araure" class="img-logo"></div>
            <h1 class="display-5 fw-bold text-gray-800 mb-3">
                Bienvenido/a, <span class="text-danger"><?php echo htmlspecialchars($userNombre . ' ' . $userApellido); ?></span> 👋
            </h1>
            <p class="lead text-muted mb-4">Ya puedes comenzar a gestionar el sistema educativo.</p>
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
<?php endif; ?>

<?php include '../../layouts/footer.php'; ?>