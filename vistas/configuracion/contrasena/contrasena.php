<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['idPersona'])) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "Acceso Denegado",
                text: "Por favor, debes iniciar sesión",
                icon: "warning",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#c90000"
            }).then(() => {
                window.location.href = "../login/login.php";
            });
        });
    </script>';
    session_destroy();
    exit();
}

require_once '../../../controladores/ContrasenaController.php';

$controller = new ContrasenaController();
$controller->manejarSolicitud();
$credenciales = $controller->obtenerCredenciales();
$alerta = $controller->alerta;
?>

<head>
    <title>UECFT Araure - Configuración de Contraseña</title>
</head>
<body>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white text-center">
                        <h4 class="mb-0"><i class='bx bxs-lock-alt'></i> Actualiza tu Contraseña</h4>
                    </div>
                    <div class="card-body p-4">

                        <!-- Mostrar alerta si existe -->
                        <?php if ($alerta): ?>
                            <?php Notificaciones::mostrar($alerta); ?>
                        <?php endif; ?>

                        <form action="" method="POST" id="añadir" novalidate>
                            <!-- Usuario -->
                            <div class="añadir__grupo" id="grupo__usuario">
                                <label for="usuario" class="form-label">Nuevo Usuario (Opcional)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                    <input 
                                        type="text" 
                                        class="form-control añadir__input" 
                                        name="usuario" 
                                        id="usuario"
                                        placeholder="Dejar vacío para mantener el actual"
                                        maxlength="20">
                                    <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                </div>
                                <p class="añadir__input-error">El usuario tiene que ser de 4 a 20 dígitos y solo puede contener números, letras y guion bajo.</p>
                            </div>

                            <!-- Contraseña Actual -->
                            <div class="añadir__grupo" id="grupo__password3">
                                <label for="password3" class="form-label">Contraseña Actual *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control añadir__input" 
                                        name="password3" 
                                        id="password3" 
                                        required 
                                        maxlength="20">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password3')">
                                        <i class="bx bx-low-vision"></i>
                                    </button>
                                    <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                </div>
                                <p class="añadir__input-error">La contraseña no coincide con la establecida actualmente.</p>
                            </div>

                            <!-- Nueva Contraseña -->
                            <div class="añadir__grupo" id="grupo__password">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control añadir__input" 
                                        name="password" 
                                        id="password" 
                                        required 
                                        maxlength="20">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="bx bx-low-vision"></i>
                                    </button>
                                    <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                </div>
                                <p class="añadir__input-error">La contraseña tiene que ser de 4 a 20 dígitos.</p>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div class="añadir__grupo" id="grupo__password2">
                                <label for="password2" class="form-label">Repetir Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control añadir__input" 
                                        name="password2" 
                                        id="password2" 
                                        required 
                                        maxlength="20">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password2')">
                                        <i class="bx bx-low-vision"></i>
                                    </button>
                                    <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                </div>
                                <p class="añadir__input-error">Ambas contraseñas deben ser iguales.</p>
                            </div>

                            <!-- Botón -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class='bx bxs-save'></i> Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/formulario.js"></script>
<script src="../../../assets/js/validacion.js"></script>
<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bx-low-vision', 'bx-show');
        } else {
            input.type = 'password';
            icon.classList.replace('bx-show', 'bx-low-vision');
        }
    }

    // Prevenir el envío del formulario si hay errores
    document.getElementById('añadir').addEventListener('submit', function (e) {
        // Ejecutar validación manual antes de enviar
        validarFormulario({ target: document.getElementById('password') });
        validarFormulario({ target: document.getElementById('password2') });

        // Verificar si todos los campos son válidos
        if (!campos.password || !campos.password3) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Por favor, corrige los errores antes de enviar.',
                confirmButtonColor: '#c90000'
            });
            return;
        }

        // Si todo está bien, el formulario se envía
    });
</script>