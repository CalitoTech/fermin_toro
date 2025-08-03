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
                window.location.href = "../../login/login.php";
            });
        });
    </script>';
    session_destroy();
    exit();
}

// Incluir Notificaciones
require_once __DIR__ . '/../../../controladores/Notificaciones.php';

// Manejo de alertas
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($alert) {
    switch ($alert) {
        case 'success':
            $alerta = Notificaciones::exito("El urbanismo se creó correctamente.");
            break;
        case 'error':
            $alerta = Notificaciones::advertencia("Este urbanismo ya existe, verifique por favor.");
            break;
        default:
            $alerta = null;
    }

    if ($alerta) {
        Notificaciones::mostrar($alerta);
    }
}

// === Cargar Roles desde el Modelo Perfil ===
$roles = [];

try {
    // Asegurarnos de que conexion.php define la clase Database
    require_once __DIR__ . '/../../../config/conexion.php';

    $database = new Database();
    $conexion = $database->getConnection();

    require_once __DIR__ . '/../../../modelos/Perfil.php';
    $perfil = new Perfil($conexion);
    $roles = $perfil->obtenerTodos();

} catch (Exception $e) {
    error_log("Error al cargar roles en nuevo_urbanismo.php: " . $e->getMessage());
    // Opcional: mostrar mensaje de advertencia
    $roles = []; // Dejar vacío si hay error
}

// === Cargar Condiciones desde la base de datos ===
$condiciones = [];

try {
    require_once __DIR__ . '/../../../modelos/Condicion.php'; // Asumo que tienes un modelo Condicion
    $condicionModel = new Condicion($conexion);
    $condiciones = $condicionModel->obtenerTodos();
} catch (Exception $e) {
    error_log("Error al cargar condiciones en nuevo_urbanismo.php: " . $e->getMessage());
    $condiciones = [];
}

// Después de cargar los roles, añade esto:
require_once __DIR__ . '/../../../modelos/TipoTelefono.php';

// Luego carga los tipos de teléfono
try {
    $tipoTelefonoModel = new TipoTelefono($conexion);
    $tiposTelefono = $tipoTelefonoModel->obtenerTodos();
} catch (Exception $e) {
    error_log("Error al cargar tipos de teléfono: " . $e->getMessage());
    $tiposTelefono = [];
}
?>

<head>
    <title>UECFT Araure - Nuevo Urbanismo</title>
</head>

<?php include '../../layouts/menu.php'; ?>
<?php include '../../layouts/header.php'; ?>

<!-- Sección Principal -->
<section class="home-section">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card shadow-sm border-0" >
                        <div class="card-header bg-danger text-white text-center">
                            <h4 class="mb-0"><i class='bx bxs-user-plus'></i> Nuevo Urbanismo</h4>
                        </div>
                        <div class="card-body p-4">

                            <form action="../../../controladores/UrbanismoController.php" method="POST" id="añadir">
                                <input type="hidden" name="action" value="crear">
                                <div class="row">
                                    <!-- Columna Izquierda -->
                                    <div class="col-md-6">
                                        <!-- Urbanismo -->
                                        <div class="añadir__grupo" id="grupo__urbanismo">
                                            <label for="urbanismo" class="form-label">Urbanismo *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class='bx bxs-user'></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control añadir__input" 
                                                    name="urbanismo" 
                                                    id="urbanismo" 
                                                    required 
                                                    maxlength="40"
                                                    onkeypress="return onlyText(event)">
                                                <i class="añadir__validacion-estado fas fa-times-circle"></i>
                                            </div>
                                            <p class="añadir__input-error">El urbanismo debe tener entre 3 y 40 letras.</p>
                                        </div>
                                    </div>

                                <!-- Botón -->
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class='bx bxs-save'></i> Guardar Urbanismo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../../layouts/footer.php'; ?>

<script src="../../../assets/js/validacion.js"></script>
<script src="../../../assets/js/formulario.js"></script>

</body>
</html>